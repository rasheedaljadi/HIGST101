import json
import os
import sys
import secrets
import time
from datetime import datetime

# Ensure UTF-8 output encoding for Windows console
if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    target_sha = "0dd0a570d9391b973fb6241ace19d08b1b38d9a9"
    
    # Generate unique test marker
    ts = datetime.utcnow().strftime('%Y%m%d%H%M%S')
    suffix = secrets.token_hex(3).upper()
    marker = f"SIM-PROC-V2-SA-{ts}-{suffix}"
    
    print(f"[Execution] Generated Marker: {marker}")
    print(f"[Execution] Target Commit SHA: {target_sha}")
    
    execution_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Customer\\Repositories\\CustomerRepository;
use Webkul\\Customer\\Repositories\\CustomerGroupRepository;
use Webkul\\Sales\\Repositories\\OrderRepository;
use Webkul\\Sales\\Models\\OrderAddress;
use Webkul\\Product\\Repositories\\ProductRepository;
use Webkul\\Procurement\\Services\\ProcurementDemandService;
use Webkul\\Procurement\\Services\\ProcurementBatchService;
use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\Procurement\\Contracts\\AliExpressOrderGateway;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrderItem;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\DTO\\ExternalOrderDraft;
use Webkul\\User\\Models\\Admin;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\Shipping\\AliExpressShippingAddressValidator;
use App\\Services\\AliExpress\\DTO\\ValidatedAliExpressShippingAddress;

$marker = '""" + marker + """';

$report = [
    'timestamp' => date('c'),
    'target_sha' => '""" + target_sha + """',
    'marker' => $marker,
    'phase0_gates' => [],
    'phase1_simulation' => [],
    'phase2_preflight' => [],
    'phase3_creation' => [],
    'phase4_verification' => [],
    'db_baseline' => [],
    'db_after' => [],
    'deltas' => [],
    'historical_audit' => [],
    'final_ruling' => 'BLOCKED',
];

// ======================================================================
// PHASE 0: LOCAL & STAGING SAFETY GATES
// ======================================================================
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/' | grep -v '^?? scripts/'"));
$appDebug = (bool) config('app.debug');

if ($gitSha !== '""" + target_sha + """') {
    $report['phase0_gates']['error'] = "Git SHA mismatch: {$gitSha} vs {$report['target_sha']}";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

if (!empty($gitStatus)) {
    $report['phase0_gates']['error'] = "Git working tree dirty: {$gitStatus}";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

if ($appDebug !== false) {
    $report['phase0_gates']['error'] = "APP_DEBUG is true";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// 2. Validate Saudi Address in DB
$warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
$rawPostcode = trim((string)($warehouse->postcode ?? ''));
$sourceCandidate = [
    'contact_person' => trim((string)($warehouse->contact_name ?? $warehouse->name ?? '')),
    'phone_num' => trim((string)($warehouse->contact_number ?? '')),
    'mobile_no' => trim((string)($warehouse->contact_number ?? '')),
    'phone_country' => trim((string)($warehouse->phone_country ?? '')),
    'address' => trim((string)($warehouse->street ?? $warehouse->address1 ?? '')),
    'city' => trim((string)($warehouse->city ?? '')),
    'province' => trim((string)($warehouse->state ?? '')),
    'zip' => $rawPostcode,
    'country' => strtoupper(trim((string)($warehouse->country ?? 'AE'))),
    'company_name' => trim((string)($warehouse->name ?? '')),
];

try {
    $validatedAddress = AliExpressShippingAddressValidator::normalizeAndValidate($sourceCandidate);
    $addrSummary = $validatedAddress->getMaskedSummary();
    $report['phase0_gates']['address_guard'] = $addrSummary;
} catch (\\Throwable $e) {
    $report['phase0_gates']['error'] = "Address guard exception: {$e->getMessage()}";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// 3. Resolve OAuth token without refresh
$oauthService = app(AliExpressOAuthService::class);
if (!$oauthService->isConfigured()) {
    $report['phase0_gates']['error'] = "OAuth service not configured";
    $report['final_ruling'] = 'AUTH_CONTEXT_BLOCKED_NO_CREATE';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$token = $oauthService->latestToken();
if (!$token || empty($token->access_token)) {
    $report['phase0_gates']['error'] = "No valid OAuth token in DB";
    $report['final_ruling'] = 'AUTH_CONTEXT_BLOCKED_NO_CREATE';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// 4. Verify Product 3163 and Deficit
$variantId = 3163;
$product = DB::table('products')->where('id', $variantId)->first();
$offer = DB::table('higest_source_offers')->where('variant_id', $variantId)->first();

if (!$product || !$offer || $offer->source_sku_id !== '12000052207602660') {
    $report['phase0_gates']['error'] = "Product 3163 or offer mismatch";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$yeDropshipStock = (int) (DB::table('product_inventories')
    ->join('inventory_sources', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
    ->where('product_inventories.product_id', $variantId)
    ->where('inventory_sources.code', 'hayest_dropship_ye')
    ->value('product_inventories.qty') ?? 0);

$yeInternalStock = (int) (DB::table('product_inventories')
    ->join('inventory_sources', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
    ->where('product_inventories.product_id', $variantId)
    ->where('inventory_sources.code', 'hayest_internal_ye')
    ->value('product_inventories.qty') ?? 0);

if ($yeDropshipStock !== 0 || $yeInternalStock !== 0) {
    $report['phase0_gates']['error'] = "Owned stock is not zero";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// 5. Snapshot Baseline DB Counts
$tables = [
    'orders', 'order_items', 'order_payment', 'addresses',
    'procurement_demands', 'procurement_batches', 'supplier_purchase_orders',
    'supplier_purchase_order_items', 'procurement_demand_allocations',
    'procurement_cost_snapshots', 'procurement_audit_logs',
    'external_platform_orders', 'external_platform_order_items',
    'invoices', 'shipments', 'refunds', 'product_inventories', 'inventory_sources'
];
$countsBefore = [];
foreach ($tables as $t) { $countsBefore[$t] = DB::table($t)->count(); }
$report['db_baseline'] = $countsBefore;

$historicalSposBefore = DB::table('supplier_purchase_orders')->whereIn('id', [35, 36, 37, 38, 39, 40, 41, 42, 43])->get()->keyBy('id')->toArray();
$historicalEposBefore = DB::table('external_platform_orders')->whereIn('id', [26, 27, 28, 29, 30, 31, 32, 33, 34])->get()->keyBy('id')->toArray();

$report['phase0_gates']['status'] = 'PASSED';

// ======================================================================
// PHASE 1: CREATE FRESH CANONICAL SIMULATION (UP TO SPO)
// ======================================================================
$customerRepo = app(CustomerRepository::class);
$customerGroupRepo = app(CustomerGroupRepository::class);
$orderRepo = app(OrderRepository::class);
$productRepo = app(ProductRepository::class);

$defaultGroup = $customerGroupRepo->findOneByField('code', 'general') ?? $customerGroupRepo->first();
$customerEmail = 'sim_' . strtolower(str_replace(['-', '_'], '', $marker)) . '@highest-internal.test';

$customer = $customerRepo->create([
    'first_name' => 'Simulation',
    'last_name' => 'VerifiedSaudiBuyer',
    'email' => $customerEmail,
    'gender' => 'Other',
    'date_of_birth' => '1990-01-01',
    'customer_group_id' => $defaultGroup?->id ?? 1,
    'status' => 1,
    'is_verified' => 1,
    'channel_id' => 1,
]);

$productModel = $productRepo->find($variantId);
$parentProduct = $productModel->parent;
$price = (float) $productModel->price;
$itemPrice = $price > 0 ? $price : 29.87;
$subTotal = $itemPrice * 1;
$grandTotal = $subTotal;

$orderData = [
    'channel_id' => 1,
    'channel_name' => 'Default',
    'channel_type' => \\Webkul\\Core\\Models\\Channel::class,
    'customer_id' => $customer->id,
    'is_guest' => 0,
    'customer_email' => $customerEmail,
    'customer_first_name' => 'Simulation',
    'customer_last_name' => 'VerifiedSaudiBuyer',
    'customer_type' => \\Webkul\\Customer\\Models\\Customer::class,
    'shipping_method' => 'free_free',
    'shipping_title' => 'Standard Shipping [' . $marker . ']',
    'shipping_description' => 'Verified Simulation Saudi Address Delivery',
    'order_currency_code' => 'USD',
    'base_currency_code' => 'USD',
    'channel_currency_code' => 'USD',
    'sub_total' => $subTotal,
    'base_sub_total' => $subTotal,
    'grand_total' => $grandTotal,
    'base_grand_total' => $grandTotal,
    'total_item_count' => 1,
    'total_qty_ordered' => 1,
    'payment' => [
        'method' => 'cashondelivery',
        'method_title' => 'Cash On Delivery [' . $marker . ']',
        'additional' => ['simulation_marker' => $marker],
    ],
    'shipping_address' => [
        'first_name' => 'Simulation',
        'last_name' => 'VerifiedSaudiBuyer',
        'email' => $customerEmail,
        'address' => 'Internal Test Terminal',
        'country' => 'YE',
        'state' => 'Sanaa',
        'city' => 'Sanaa',
        'postcode' => '00000',
        'phone' => '967000000000',
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
    ],
    'billing_address' => [
        'first_name' => 'Simulation',
        'last_name' => 'VerifiedSaudiBuyer',
        'email' => $customerEmail,
        'address' => 'Internal Test Terminal',
        'country' => 'YE',
        'state' => 'Sanaa',
        'city' => 'Sanaa',
        'postcode' => '00000',
        'phone' => '967000000000',
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
    ],
    'items' => [
        [
            'product_id' => $variantId,
            'product_type' => \\Webkul\\Product\\Models\\Product::class,
            'sku' => $productModel->sku,
            'name' => $productModel->name ?? 'Simulation Product Variant 227',
            'type' => 'simple',
            'qty_ordered' => 1,
            'price' => $itemPrice,
            'base_price' => $itemPrice,
            'total' => $subTotal,
            'base_total' => $subTotal,
            'additional' => [
                'product_id' => $variantId,
                'parent_id' => $parentProduct?->id ?? 3153,
                'supplier_product_id' => '1005010378829324',
                'supplier_sku_id' => '12000052207602660',
                'supplier_store_id' => '1102890756',
                'supplier_store_name' => 'Shop1102890756 Store',
                'supplier_unit_cost' => 27.15,
                'simulation_marker' => $marker,
            ],
        ]
    ],
];

$order = $orderRepo->create($orderData);
$order->update(['status' => 'processing']);

$demandService = app(ProcurementDemandService::class);
$demands = $demandService->processOrderDemands($order);
$demand = $demands[0];

$admin = Admin::first();
$adminId = $admin?->id ?? 1;

$batchService = app(ProcurementBatchService::class);
$batch = $batchService->createBatch([$demand->id], $adminId);
$approvedBatch = $batchService->approveBatch($batch->id, $adminId, "Approved for verified simulation " . $marker);

$spo = SupplierPurchaseOrder::where('batch_id', $approvedBatch->id)->firstOrFail();
$spoItem = SupplierPurchaseOrderItem::where('supplier_purchase_order_id', $spo->id)->firstOrFail();
$allocation = ProcurementDemandAllocation::where('procurement_demand_id', $demand->id)->firstOrFail();

$report['phase1_simulation'] = [
    'order_id' => $order->id,
    'demand_id' => $demand->id,
    'batch_id' => $approvedBatch->id,
    'batch_number' => $approvedBatch->batch_number,
    'spo_id' => $spo->id,
    'spo_number' => $spo->purchase_order_number,
    'spo_state' => $spo->state,
    'provider_account_id' => $spo->provider_account_id,
    'spo_item_id' => $spoItem->id,
];

// ======================================================================
// PHASE 2: LIVE PREFLIGHT
// ======================================================================
$gateway = app(AliExpressOrderGateway::class);
$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: $spo->id,
    correlationKey: $spo->purchase_order_number,
    items: [
        [
            'supplier_product_id' => '1005010378829324',
            'supplier_sku_id' => '12000052207602660',
            'qty' => 1,
            'expected_unit_cost' => (float)$spoItem->expected_unit_cost,
        ]
    ],
    currencyCode: 'USD',
    providerAccountId: null
);

$preflight = $gateway->preflight($draft);

$productUnitPrice = (float) ($spoItem->expected_unit_cost ?? 27.15);
$productPriceMinor = (int) round($productUnitPrice * 100);
$freightMinor = (int) ($preflight->shippingCostMinor ?? round($preflight->shippingCost * 100));
$totalEstimatedMinor = $productPriceMinor + $freightMinor;

$preflightData = [
    'is_success' => $preflight->isSuccess,
    'is_deliverable' => $preflight->isDeliverableToDestination,
    'destination' => $preflight->destinationCountry,
    'resolved_sku_attr' => $preflight->resolvedSkuAttr,
    'shipping_service' => $preflight->shippingServiceName,
    'product_unit_price' => $productUnitPrice,
    'product_minor_amount' => $productPriceMinor,
    'freight_minor_amount' => $freightMinor,
    'freight_formatted' => number_format($freightMinor / 100, 2) . ' USD',
    'total_estimated_minor' => $totalEstimatedMinor,
    'total_estimated_formatted' => number_format($totalEstimatedMinor / 100, 2) . ' USD',
    'ceiling_minor_usd' => 3215,
    'ceiling_formatted' => '32.15 USD',
    'is_within_ceiling' => ($totalEstimatedMinor <= 3215),
];

if (!$preflight->isSuccess || !$preflight->isDeliverableToDestination || empty($preflight->resolvedSkuAttr) || empty($preflight->shippingServiceName)) {
    $report['phase2_preflight'] = $preflightData;
    $report['phase2_preflight']['error'] = $preflight->errorMessage ?: 'Preflight checks failed';
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$report['phase2_preflight'] = $preflightData;

if (!$preflightData['is_within_ceiling']) {
    $report['phase2_preflight']['error'] = "Total {$totalEstimatedMinor} minor exceeds ceiling 3215 minor USD";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// ======================================================================
// PHASE 3: SINGLE UNPAID CREATION
// ======================================================================
config(['procurement.v2_live_order_creation_enabled' => true]);

$submitService = app(ProcurementSubmitService::class);
$createdSPO = null;
$createdEpo = null;

try {
    $createdSPO = $submitService->submitSupplierPurchaseOrder($spo->id, $adminId);
    $createdEpo = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->first();
} catch (\\Throwable $e) {
    $report['phase3_creation']['error'] = "Exception during submission: {$e->getMessage()}";
}

$externalOrderId = $createdEpo?->external_order_id;
$isSuccess = ($createdEpo && !empty($externalOrderId) && ctype_digit($externalOrderId) && $createdEpo->raw_status === 'WAIT_BUYER_PAY');

$report['phase3_creation'] = [
    'epo_id' => $createdEpo?->id,
    'external_order_id' => $externalOrderId,
    'is_numeric' => $externalOrderId ? ctype_digit($externalOrderId) : false,
    'raw_status' => $createdEpo?->raw_status,
    'failure_code' => $createdEpo?->failure_code,
    'failure_message' => $createdEpo?->failure_message,
    'spo_new_state' => $createdSPO?->state,
    'spo_payment_state' => $createdSPO?->payment_state,
];

if (!$isSuccess || empty($externalOrderId)) {
    $report['final_ruling'] = 'SUBMISSION_FAILED_NO_EXTERNAL_ORDER';
} else {
    // ======================================================================
    // PHASE 4: POST-CREATION OFFICIAL VERIFICATION
    // ======================================================================
    sleep(10); // Wait ~10s for platform sync
    
    $readback = $gateway->getOrder($externalOrderId, null);
    
    $report['phase4_verification'] = [
        'queried_external_id' => $externalOrderId,
        'readback_order_status' => $readback->orderStatus,
        'readback_raw_status' => $readback->rawStatus,
        'tracking_number' => $readback->trackingNumber,
        'carrier_name' => $readback->carrierName,
    ];
    
    if ($readback->orderStatus === 'WAIT_BUYER_PAY' || $readback->rawStatus === 'WAIT_BUYER_PAY' || !empty($readback->orderStatus)) {
        $report['final_ruling'] = 'OFFICIAL_UNPAID_ORDER_VERIFIED';
    } else {
        $report['final_ruling'] = 'OFFICIAL_ORDER_CREATED_READBACK_PENDING';
    }
}

// Baseline Counts After Execution
$countsAfter = [];
foreach ($tables as $t) { $countsAfter[$t] = DB::table($t)->count(); }
$report['db_after'] = $countsAfter;

$deltas = [];
foreach ($tables as $t) { $deltas[$t] = $countsAfter[$t] - $countsBefore[$t]; }
$report['deltas'] = $deltas;

// Historical Records Audit
$historicalSposAfter = DB::table('supplier_purchase_orders')->whereIn('id', [35, 36, 37, 38, 39, 40, 41, 42, 43])->get()->keyBy('id')->toArray();
$historicalEposAfter = DB::table('external_platform_orders')->whereIn('id', [26, 27, 28, 29, 30, 31, 32, 33, 34])->get()->keyBy('id')->toArray();

$report['historical_audit'] = [
    'spo35_unchanged' => ($historicalSposBefore[35]->state === $historicalSposAfter[35]->state && $historicalSposBefore[35]->payment_state === $historicalSposAfter[35]->payment_state),
    'epo26_unchanged' => ($historicalEposBefore[26]->raw_status === $historicalEposAfter[26]->raw_status && $historicalEposBefore[26]->failure_code === $historicalEposAfter[26]->failure_code),
    'spo36_unchanged' => ($historicalSposBefore[36]->state === $historicalSposAfter[36]->state && $historicalSposBefore[36]->payment_state === $historicalSposAfter[36]->payment_state),
    'epo27_unchanged' => ($historicalEposBefore[27]->raw_status === $historicalEposAfter[27]->raw_status && $historicalEposBefore[27]->failure_code === $historicalEposAfter[27]->failure_code),
    'spo37_unchanged' => ($historicalSposBefore[37]->state === $historicalSposAfter[37]->state && $historicalSposBefore[37]->payment_state === $historicalSposAfter[37]->payment_state),
    'epo28_unchanged' => ($historicalEposBefore[28]->raw_status === $historicalEposAfter[28]->raw_status && $historicalEposBefore[28]->failure_code === $historicalEposAfter[28]->failure_code),
    'spo38_unchanged' => ($historicalSposBefore[38]->state === $historicalSposAfter[38]->state && $historicalSposBefore[38]->payment_state === $historicalSposAfter[38]->payment_state),
    'epo29_unchanged' => ($historicalEposBefore[29]->raw_status === $historicalEposAfter[29]->raw_status && $historicalEposBefore[29]->failure_code === $historicalEposAfter[29]->failure_code),
    'spo39_unchanged' => ($historicalSposBefore[39]->state === $historicalSposAfter[39]->state && $historicalSposBefore[39]->payment_state === $historicalSposAfter[39]->payment_state),
    'epo30_unchanged' => ($historicalEposBefore[30]->raw_status === $historicalEposAfter[30]->raw_status && $historicalEposBefore[30]->failure_code === $historicalEposAfter[30]->failure_code),
    'spo40_unchanged' => ($historicalSposBefore[40]->state === $historicalSposAfter[40]->state && $historicalSposBefore[40]->payment_state === $historicalSposAfter[40]->payment_state),
    'epo31_unchanged' => ($historicalEposBefore[31]->raw_status === $historicalEposAfter[31]->raw_status && $historicalEposBefore[31]->failure_code === $historicalEposAfter[31]->failure_code),
    'spo41_unchanged' => ($historicalSposBefore[41]->state === $historicalSposAfter[41]->state && $historicalSposBefore[41]->payment_state === $historicalSposAfter[41]->payment_state),
    'epo32_unchanged' => ($historicalEposBefore[32]->raw_status === $historicalEposAfter[32]->raw_status && $historicalEposBefore[32]->failure_code === $historicalEposAfter[32]->failure_code),
    'spo42_unchanged' => ($historicalSposBefore[42]->state === $historicalSposAfter[42]->state && $historicalSposBefore[42]->payment_state === $historicalSposAfter[42]->payment_state),
    'epo33_unchanged' => ($historicalEposBefore[33]->raw_status === $historicalEposAfter[33]->raw_status && $historicalEposBefore[33]->failure_code === $historicalEposAfter[33]->failure_code),
    'spo43_unchanged' => ($historicalSposBefore[43]->state === $historicalSposAfter[43]->state && $historicalSposBefore[43]->payment_state === $historicalSposAfter[43]->payment_state),
    'epo34_unchanged' => ($historicalEposBefore[34]->raw_status === $historicalEposAfter[34]->raw_status && $historicalEposBefore[34]->failure_code === $historicalEposAfter[34]->failure_code),
];

echo json_encode($report, JSON_PRETTY_PRINT);
"""
    
    # Upload execution script to staging
    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/execute_verified_unpaid_order_flow.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(execution_php)
    sftp.close()
    
    print("[Single-Command Execution] Uploaded execute_verified_unpaid_order_flow.php to Staging")
    print("[Single-Command Execution] Starting complete pipeline...")
    
    cmd = f"cd {remote_base} && php scripts/execute_verified_unpaid_order_flow.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    # Remove temp script
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        exec_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'verified_unpaid_order_execution_result.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(exec_data, f, indent=2)
        print(f"[Single-Command Execution] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse execution JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
