import json
import os
import sys
import secrets
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
    target_sha = "fffd0d1c42cefd9b10dc63e307c083dd9f83ef40"
    
    # Generate unique test marker: SIM-PROC-V2-CTX-<timestamp>-<suffix>
    ts = datetime.utcnow().strftime('%Y%m%d%H%M%S')
    suffix = secrets.token_hex(3).upper()
    marker = f"SIM-PROC-V2-CTX-{ts}-{suffix}"
    
    print(f"[Simulation] Generated Marker: {marker}")
    print(f"[Simulation] Target Commit SHA: {target_sha}")
    
    simulation_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Webkul\\Customer\\Repositories\\CustomerRepository;
use Webkul\\Customer\\Repositories\\CustomerGroupRepository;
use Webkul\\Sales\\Repositories\\OrderRepository;
use Webkul\\Sales\\Models\\OrderAddress;
use Webkul\\Product\\Repositories\\ProductRepository;
use Webkul\\Procurement\\Services\\ProcurementDemandService;
use Webkul\\Procurement\\Services\\ProcurementBatchService;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrderItem;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;
use Webkul\\User\\Models\\Admin;

$marker = '""" + marker + """';

// 1. Check Pre-Execution Gates
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/' | grep -v '^?? scripts/'"));
$appDebug = (bool) config('app.debug');

if ($gitSha !== '""" + target_sha + """') {
    echo json_encode(['error' => 'GATE_FAILED: Git SHA mismatch', 'actual' => $gitSha, 'expected' => '""" + target_sha + """']);
    exit(1);
}

if (!empty($gitStatus)) {
    echo json_encode(['error' => 'GATE_FAILED: Git working tree is dirty', 'status' => $gitStatus]);
    exit(1);
}

if ($appDebug !== false) {
    echo json_encode(['error' => 'GATE_FAILED: APP_DEBUG is true']);
    exit(1);
}

// 2. Verify Product 3163 and Deficit
$variantId = 3163;
$product = DB::table('products')->where('id', $variantId)->first();
$offer = DB::table('higest_source_offers')->where('variant_id', $variantId)->first();

if (!$product || !$offer || $offer->source_sku_id !== '12000052207602660') {
    echo json_encode(['error' => 'SIMULATION_BLOCKED_PRODUCT_OR_DEFICIT_CHANGED: Product/offer mismatch']);
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
    echo json_encode(['error' => 'SIMULATION_BLOCKED_PRODUCT_OR_DEFICIT_CHANGED: Owned stock is not zero']);
    exit(1);
}

// 3. Check Marker Uniqueness
$existingMarkerOrder = DB::table('orders')->where('shipping_title', 'like', "%$marker%")->first();
if ($existingMarkerOrder) {
    echo json_encode(['error' => 'SIMULATION_MARKER_ALREADY_EXISTS']);
    exit(1);
}

// 4. Baseline Counts Before Execution
$tables = [
    'orders',
    'order_items',
    'order_payment',
    'addresses',
    'procurement_demands',
    'procurement_batches',
    'supplier_purchase_orders',
    'supplier_purchase_order_items',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'procurement_audit_logs',
    'external_platform_orders',
    'invoices',
    'shipments',
    'refunds',
    'jobs',
    'failed_jobs',
    'product_inventories',
    'inventory_sources'
];

$countsBefore = [];
foreach ($tables as $t) {
    $countsBefore[$t] = DB::table($t)->count();
}

$spo35Before = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26Before = DB::table('external_platform_orders')->where('id', 26)->first();

// 5. Execute Canonical Simulation
$customerRepo = app(CustomerRepository::class);
$customerGroupRepo = app(CustomerGroupRepository::class);
$orderRepo = app(OrderRepository::class);
$productRepo = app(ProductRepository::class);

// Step 5.1: Create isolated test customer
$defaultGroup = $customerGroupRepo->findOneByField('code', 'general') ?? $customerGroupRepo->first();
$customerEmail = 'sim_' . strtolower(str_replace(['-', '_'], '', $marker)) . '@highest-internal.test';

$customer = $customerRepo->create([
    'first_name' => 'Simulation',
    'last_name' => 'ContextBuyer',
    'email' => $customerEmail,
    'gender' => 'Other',
    'date_of_birth' => '1990-01-01',
    'customer_group_id' => $defaultGroup?->id ?? 1,
    'status' => 1,
    'is_verified' => 1,
    'channel_id' => 1,
]);

// Step 5.2: Create Order for product 3163 via canonical Bagisto structure
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
    'customer_last_name' => 'ContextBuyer',
    'customer_type' => \\Webkul\\Customer\\Models\\Customer::class,
    'shipping_method' => 'free_free',
    'shipping_title' => 'Standard Shipping [' . $marker . ']',
    'shipping_description' => 'Simulation Context Delivery',
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
        'last_name' => 'ContextBuyer',
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
        'last_name' => 'ContextBuyer',
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
                // Crucial: NO provider_account_id specified (resolves to null)
            ],
        ]
    ],
];

$order = $orderRepo->create($orderData);
$order->update(['status' => 'processing']);

// Reload order
$order = $orderRepo->find($order->id);

// Step 5.3: Invoke ProcurementDemandService
$demandService = app(ProcurementDemandService::class);
$demands = $demandService->processOrderDemands($order);

if (empty($demands) || count($demands) !== 1) {
    throw new \\Exception("Expected 1 ProcurementDemand, got " . count($demands));
}

$demand = $demands[0];
if ($demand->state !== ProcurementDemand::STATE_OPEN_FOR_BATCHING) {
    throw new \\Exception("Demand state is '" . $demand->state . "', expected 'open_for_batching'");
}

// Step 5.4: Invoke ProcurementBatchService
$admin = Admin::first();
$adminId = $admin?->id ?? 1;

$batchService = app(ProcurementBatchService::class);
$batch = $batchService->createBatch([$demand->id], $adminId);

if ($batch->state !== ProcurementBatch::STATE_READY_FOR_REVIEW) {
    throw new \\Exception("Batch state is '" . $batch->state . "', expected 'ready_for_review'");
}

// Step 5.5: Approve Batch
$approvedBatch = $batchService->approveBatch($batch->id, $adminId, "Approved for simulation " . $marker);

// Reload fresh SPO and components
$spo = SupplierPurchaseOrder::where('batch_id', $approvedBatch->id)->firstOrFail();
$spoItem = SupplierPurchaseOrderItem::where('supplier_purchase_order_id', $spo->id)->firstOrFail();
$allocation = ProcurementDemandAllocation::where('procurement_demand_id', $demand->id)->firstOrFail();

$result = [
    'marker' => $marker,
    'order_id' => $order->id,
    'order_increment_id' => $order->increment_id,
    'customer_id' => $customer->id,
    'order_item_id' => $order->items->first()?->id,
    'demand_id' => $demand->id,
    'demand_state' => $demand->fresh()->state,
    'demand_provider_account_id' => $demand->fresh()->provider_account_id,
    'batch_id' => $approvedBatch->id,
    'batch_number' => $approvedBatch->batch_number,
    'batch_state' => $approvedBatch->state,
    'spo_id' => $spo->id,
    'spo_number' => $spo->purchase_order_number,
    'spo_state' => $spo->state,
    'spo_provider_account_id' => $spo->provider_account_id,
    'spo_item_id' => $spoItem->id,
    'spo_item_product_id' => $spoItem->supplier_product_id,
    'spo_item_sku_id' => $spoItem->supplier_sku_id,
    'allocation_id' => $allocation->id,
    'allocation_state' => $allocation->state,
];

// 6. Baseline Counts After Execution
$countsAfter = [];
foreach ($tables as $t) {
    $countsAfter[$t] = DB::table($t)->count();
}

$deltas = [];
foreach ($tables as $t) {
    $deltas[$t] = $countsAfter[$t] - $countsBefore[$t];
}

$spo35After = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26After = DB::table('external_platform_orders')->where('id', 26)->first();

$spo35Unchanged = (
    $spo35Before->state === $spo35After->state &&
    $spo35Before->payment_state === $spo35After->payment_state &&
    $spo35After->state === 'supplier_exception'
);

$epo26Unchanged = (
    $epo26Before->raw_status === $epo26After->raw_status &&
    $epo26Before->failure_code === $epo26After->failure_code &&
    $epo26After->external_order_id === null
);

// 7. Verify Invariants
$invariantsPassed = (
    $deltas['orders'] === 1 &&
    $deltas['order_items'] === 1 &&
    $deltas['order_payment'] === 1 &&
    $deltas['addresses'] === 2 &&
    $deltas['procurement_demands'] === 1 &&
    $deltas['procurement_batches'] === 1 &&
    $deltas['supplier_purchase_orders'] === 1 &&
    $deltas['supplier_purchase_order_items'] === 1 &&
    $deltas['procurement_demand_allocations'] === 1 &&
    $deltas['external_platform_orders'] === 0 &&
    $deltas['invoices'] === 0 &&
    $deltas['shipments'] === 0 &&
    $deltas['refunds'] === 0 &&
    $deltas['product_inventories'] === 0 &&
    $result['spo_state'] === SupplierPurchaseOrder::STATE_READY_TO_SUBMIT &&
    $result['batch_state'] === ProcurementBatch::STATE_APPROVED &&
    $result['demand_provider_account_id'] === null &&
    $result['spo_provider_account_id'] === null &&
    $spo35Unchanged &&
    $epo26Unchanged
);

echo json_encode([
    'ruling' => $invariantsPassed ? 'PROVIDER_CONTEXT_NEW_SIMULATION_SPO_READY_FOR_FRESH_PREFLIGHT' : 'SIMULATION_BLOCKED_OR_FAILED — Invariants check failed',
    'marker' => $marker,
    'invariants_passed' => $invariantsPassed,
    'result' => $result,
    'counts_before' => $countsBefore,
    'counts_after' => $countsAfter,
    'deltas' => $deltas,
    'spo35_unchanged' => $spo35Unchanged,
    'epo26_unchanged' => $epo26Unchanged,
], JSON_PRETTY_PRINT);
?>""";

    # Upload simulation script to staging
    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/execute_simulation_proc_v2_ctx.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(simulation_php)
    sftp.close()
    
    print("[SSH] Uploaded simulation script. Executing on Staging...")
    
    cmd = f"cd {remote_base} && php scripts/execute_simulation_proc_v2_ctx.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Staging Execution Output ---\n{out}")
    if err:
        print(f"\n--- Staging STDERR ---\n{err}")
        
    client.close()
    
    # Save output locally
    try:
        data = json.loads(out)
        with open('scripts/new_simulation_execution_result.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("\n[Result] Saved to scripts/new_simulation_execution_result.json")
    except Exception as e:
        print(f"[Result] Could not parse JSON output: {e}")

if __name__ == '__main__':
    main()
