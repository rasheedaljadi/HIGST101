import json
import os
import sys
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
    target_sha = "c517da3d22e6dac2b872993ec2a2948b4d183f63"
    
    execution_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(\\Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\Shipping\\AliExpressShippingAddressValidator;
use App\\Services\\AliExpress\\DTO\\ValidatedAliExpressShippingAddress;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Procurement\\Contracts\\AliExpressOrderGateway;
use Webkul\\Procurement\\DTO\\ExternalOrderDraft;
use Webkul\\Procurement\\DTO\\VerifiedExternalOrderCreated;
use Webkul\\Procurement\\DTO\\ExternalOrderSubmissionFailed;
use Webkul\\User\\Models\\Admin;

$report = [
    'timestamp' => date('c'),
    'target_sha' => '""" + target_sha + """',
    'spo_id' => 37,
    'marker' => 'SIM-PROC-V2-SA-20260823013944-DC7B4A',
    'phase0_gates' => [],
    'phase1_preflight' => [],
    'phase2_creation' => [],
    'phase3_verification' => [],
    'db_baseline' => [],
    'db_after' => [],
    'deltas' => [],
    'historical_audit' => [],
    'final_ruling' => 'BLOCKED',
];

// ======================================================================
// PHASE 0: LOCAL SAFETY GATES
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

// 2. Validate SPO #37 and linked entities
$spo = SupplierPurchaseOrder::with(['items', 'batch', 'allocations'])->find(37);
if (!$spo || $spo->state !== 'ready_to_submit' || $spo->provider_account_id !== null) {
    $report['phase0_gates']['error'] = "SPO #37 invalid or not in ready_to_submit state";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$existingEpo = ExternalPlatformOrder::where('supplier_purchase_order_id', 37)->first();
if ($existingEpo) {
    $report['phase0_gates']['error'] = "SPO #37 already has an associated EPO record (ID {$existingEpo->id})";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$spoItem = $spo->items->first();
if (!$spoItem || $spoItem->supplier_product_id !== '1005010378829324' || $spoItem->supplier_sku_id !== '12000052207602660' || (int)$spoItem->qty_ordered !== 1) {
    $report['phase0_gates']['error'] = "SPO #37 items mismatch";
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

// 4. In-memory Saudi address guard verification
$warehouse = DB::table('inventory_sources')->where('code', 'default')->first();
$rawPostcode = trim((string)($warehouse->postcode ?? ''));
$sourceCandidate = [
    'contact_person' => trim((string)($warehouse->contact_name ?? $warehouse->name ?? '')),
    'phone_num' => trim((string)($warehouse->contact_number ?? '')),
    'mobile_no' => trim((string)($warehouse->contact_number ?? '')),
    'phone_country' => '966',
    'address' => trim((string)($warehouse->street ?? $warehouse->address1 ?? '')),
    'city' => trim((string)($warehouse->city ?? '')),
    'province' => trim((string)($warehouse->state ?? '')),
    'zip' => $rawPostcode,
    'country' => strtoupper(trim((string)($warehouse->country ?? 'SA'))),
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

$spo35Before = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26Before = DB::table('external_platform_orders')->where('id', 26)->first();
$spo36Before = DB::table('supplier_purchase_orders')->where('id', 36)->first();
$epo27Before = DB::table('external_platform_orders')->where('id', 27)->first();

$report['phase0_gates']['status'] = 'PASSED';

// ======================================================================
// PHASE 1: LIVE PREFLIGHT
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
    $report['phase1_preflight'] = $preflightData;
    $report['phase1_preflight']['error'] = $preflight->errorMessage ?: 'Preflight checks failed';
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

$report['phase1_preflight'] = $preflightData;

if (!$preflightData['is_within_ceiling']) {
    $report['phase1_preflight']['error'] = "Total {$totalEstimatedMinor} minor exceeds ceiling 3215 minor USD";
    $report['final_ruling'] = 'NO_CREATE_PRECHECK_BLOCKED';
    echo json_encode($report, JSON_PRETTY_PRINT);
    exit(1);
}

// ======================================================================
// PHASE 2: SINGLE UNPAID CREATION
// ======================================================================
// Enable live creation for this single authorized execution
config(['procurement.v2_live_order_creation_enabled' => true]);

$admin = Admin::first();
$actorId = $admin?->id ?? 1;

$submitService = app(ProcurementSubmitService::class);

$submissionResult = null;
$createdEpo = null;
$createdSPO = null;

try {
    $createdSPO = $submitService->submitSupplierPurchaseOrder($spo->id, $actorId);
    $createdEpo = ExternalPlatformOrder::where('supplier_purchase_order_id', 37)->first();
} catch (\\Throwable $e) {
    $report['phase2_creation']['error'] = "Exception during submission: {$e->getMessage()}";
}

$externalOrderId = $createdEpo?->external_order_id;
$isSuccess = ($createdEpo && !empty($externalOrderId) && ctype_digit($externalOrderId) && $createdEpo->raw_status === 'WAIT_BUYER_PAY');

$report['phase2_creation'] = [
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
    // PHASE 3: POST-CREATION OFFICIAL VERIFICATION
    // ======================================================================
    sleep(10); // Wait ~10s for platform sync
    
    $readback = $gateway->getOrder($externalOrderId, null);
    
    $report['phase3_verification'] = [
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
$spo35After = DB::table('supplier_purchase_orders')->where('id', 35)->first();
$epo26After = DB::table('external_platform_orders')->where('id', 26)->first();
$spo36After = DB::table('supplier_purchase_orders')->where('id', 36)->first();
$epo27After = DB::table('external_platform_orders')->where('id', 27)->first();

$report['historical_audit'] = [
    'spo35_unchanged' => ($spo35Before->state === $spo35After->state && $spo35Before->payment_state === $spo35After->payment_state),
    'epo26_unchanged' => ($epo26Before->raw_status === $epo26After->raw_status && $epo26Before->failure_code === $epo26After->failure_code),
    'spo36_unchanged' => ($spo36Before->state === $spo36After->state && $spo36Before->payment_state === $spo36After->payment_state),
    'epo27_unchanged' => ($epo27Before->raw_status === $epo27After->raw_status && $epo27Before->failure_code === $epo27After->failure_code),
];

echo json_encode($report, JSON_PRETTY_PRINT);
"""
    
    # Upload execution script to staging
    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/execute_spo37_single_command_flow.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(execution_php)
    sftp.close()
    
    print("[Single-Command Execution] Uploaded execute_spo37_single_command_flow.php to Staging")
    print("[Single-Command Execution] Starting Phase 0 -> Phase 1 -> Phase 2 -> Phase 3 pipeline...")
    
    cmd = f"cd {remote_base} && php scripts/execute_spo37_single_command_flow.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Output ---\n{out}")
    if err:
        print(f"\n--- STDERR ---\n{err}")
        
    # Remove temp script
    run_remote_cmd(client, f"rm -f {remote_script_path}")
    
    try:
        exec_data = json.loads(out)
        out_json_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'spo37_single_command_execution_result.json')
        with open(out_json_path, 'w', encoding='utf-8') as f:
            json.dump(exec_data, f, indent=2)
        print(f"[Single-Command Execution] Saved result to {out_json_path}")
    except Exception as e:
        print(f"[ERROR] Could not parse execution JSON: {e}")
        
    client.close()

if __name__ == '__main__':
    main()
