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
    target_sha = "fffd0d1c42cefd9b10dc63e307c083dd9f83ef40"
    spo_id = 36
    
    script_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use Webkul\\Procurement\\Contracts\\AliExpressAuthorizationContextResolver;
use Webkul\\Procurement\\Contracts\\AliExpressOrderGateway;
use Webkul\\Procurement\\DTO\\ExternalOrderDraft;
use Webkul\\Procurement\\DTO\\VerifiedExternalOrderCreated;
use Webkul\\Procurement\\DTO\\ExternalOrderSubmissionFailed;
use Webkul\\Procurement\\Exceptions\\AliExpressAuthorizationUnavailableException;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\ExternalPlatformOrder;
use Webkul\\Procurement\\Models\\ExternalPlatformOrderItem;
use Webkul\\User\\Models\\Admin;

// 1. Check Pre-Execution Gates
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/' | grep -v '^?? scripts/'"));
$fileSha = trim(shell_exec('sha256sum ' . escapeshellarg($projDir . '/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php') . " | awk '{print $1}'"));
$blobSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git show HEAD:packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | sha256sum | awk '{print $1}'"));

$gitClean = ($gitSha === '""" + target_sha + """' && $fileSha === $blobSha && empty($gitStatus));
$appDebug = (bool) config('app.debug');

if (!$gitClean || $appDebug !== false) {
    echo json_encode([
        'ruling' => 'NO_CREATE_DUE_TO_GIT_OR_APP_ENVIRONMENT_NOT_PRISTINE',
        'git_sha' => $gitSha,
        'git_clean' => $gitClean,
        'app_debug' => $appDebug,
    ]);
    exit(1);
}

// 2. Verify Expiry Window (Window: 03:57:15 +03:00)
$expiryTs = strtotime('2026-08-23 03:57:15 +03:00');
if (time() > $expiryTs) {
    echo json_encode([
        'ruling' => 'APPROVAL_EXPIRED_REQUIRES_FRESH_PREFLIGHT_AND_CONFIRMATION',
        'current_time' => date('Y-m-d H:i:s P'),
        'expiry_time' => '2026-08-23 03:57:15 +03:00',
    ]);
    exit(1);
}

// 3. Inspect SPO #36 and Context
$spo = SupplierPurchaseOrder::with(['items', 'batch'])->find(""" + str(spo_id) + """);

if (!$spo) {
    echo json_encode(['ruling' => 'NO_CREATE_DUE_TO_SPO_NOT_FOUND']);
    exit(1);
}

if ($spo->state !== SupplierPurchaseOrder::STATE_READY_TO_SUBMIT) {
    echo json_encode(['ruling' => "NO_CREATE_DUE_TO_SPO_STATE_NOT_READY_TO_SUBMIT (Current: {$spo->state})"]);
    exit(1);
}

if ($spo->provider_account_id !== null) {
    echo json_encode(['ruling' => "NO_CREATE_DUE_TO_SPO_PROVIDER_ACCOUNT_ID_NOT_NULL ({$spo->provider_account_id})"]);
    exit(1);
}

$hasLinkedEpo = DB::table('external_platform_orders')->where('supplier_purchase_order_id', $spo->id)->exists();
if ($hasLinkedEpo) {
    echo json_encode(['ruling' => 'NO_CREATE_DUE_TO_SPO_ALREADY_LINKED_TO_EPO']);
    exit(1);
}

$firstItem = $spo->items->first();
if (!$firstItem || $firstItem->supplier_product_id !== '1005010378829324' || $firstItem->supplier_sku_id !== '12000052207602660' || (int)$firstItem->qty_ordered !== 1) {
    echo json_encode(['ruling' => 'NO_CREATE_DUE_TO_SPO_ITEM_CONTEXT_MISMATCH']);
    exit(1);
}

// 4. Verify Authorization Context via Resolver (No refresh)
$authResolver = app(AliExpressAuthorizationContextResolver::class);
try {
    $authContext = $authResolver->resolveForDropshipperSubmission(null);
} catch (AliExpressAuthorizationUnavailableException $e) {
    echo json_encode([
        'ruling' => 'NO_CREATE_DUE_TO_AUTHORIZATION_UNAVAILABLE',
        'error_code' => $e->errorCode,
        'error_message' => $e->getMessage(),
    ]);
    exit(1);
}

$authSummary = $authContext->getMaskedSummary();

// 5. Baseline Counts Before Execution
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
    'external_platform_order_items',
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

// 6. Preflight Verification Gate (Ceiling: 3215 minor cents = $32.15 USD)
$gateway = app(AliExpressOrderGateway::class);
$preflightDraft = new ExternalOrderDraft(
    supplierPurchaseOrderId: $spo->id,
    correlationKey: 'PRE-SUBMIT-SPO-36-' . strtoupper(bin2hex(random_bytes(4))),
    items: [
        [
            'supplier_product_id' => $firstItem->supplier_product_id,
            'supplier_sku_id' => $firstItem->supplier_sku_id,
            'qty' => (int) $firstItem->qty_ordered,
            'currency_code' => 'USD',
        ]
    ],
    providerAccountId: null
);

$preflight = $gateway->preflight($preflightDraft);

if (!$preflight->isSuccess || !$preflight->isDeliverableToDestination || empty($preflight->shippingServiceName)) {
    echo json_encode([
        'ruling' => 'NO_CREATE_DUE_TO_PREFLIGHT_VALIDATION_FAILED',
        'error_code' => $preflight->errorCode,
        'error_message' => $preflight->errorMessage,
    ]);
    exit(1);
}

// Check live unit price
$apiClient = app(AliExpressApiClient::class);
$prodRes = $apiClient->call('aliexpress.ds.product.get', $authContext->accessToken, [
    'product_id' => $firstItem->supplier_product_id,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$unitPriceMinor = null;
if ($prodRes['ok']) {
    $body = $prodRes['body'];
    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
    $variants = $resp['result']['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
    foreach ($variants as $v) {
        if (($v['sku_id'] ?? '') == $firstItem->supplier_sku_id) {
            $rawPrice = $v['offer_sale_price'] ?? $v['sku_price'] ?? null;
            if ($rawPrice !== null && is_numeric($rawPrice)) {
                $unitPriceMinor = (int) round(((float)$rawPrice) * 100);
            }
            break;
        }
    }
}

if ($unitPriceMinor === null) {
    echo json_encode(['ruling' => 'NO_CREATE_DUE_TO_UNIT_PRICE_UNRESOLVED']);
    exit(1);
}

$freightMinor = $preflight->shippingCostMinor ?? 0;
$totalMinor = ($unitPriceMinor * (int)$firstItem->qty_ordered) + $freightMinor;

if ($totalMinor > 3215) {
    echo json_encode([
        'ruling' => 'NO_CREATE_DUE_TO_PRICE_CEILING_EXCEEDED',
        'calculated_minor' => $totalMinor,
        'ceiling_minor' => 3215,
    ]);
    exit(1);
}

// 7. Execute Authorized Single Submission via ProcurementSubmitService
config(['procurement.v2_live_order_creation_enabled' => true]);

$admin = Admin::first();
$adminId = $admin?->id ?? 1;

$submitService = app(ProcurementSubmitService::class);
$submissionException = null;
$updatedSpo = null;

try {
    $updatedSpo = $submitService->submitSupplierPurchaseOrder($spo->id, $adminId);
} catch (\\Throwable $e) {
    $submissionException = $e->getMessage();
}

// 8. Inspect Created ExternalPlatformOrder
$epo = ExternalPlatformOrder::where('supplier_purchase_order_id', $spo->id)->first();
$externalOrderId = $epo?->external_order_id;
$isNumericId = (!empty($externalOrderId) && ctype_digit((string)$externalOrderId) && strlen((string)$externalOrderId) >= 10);

$submissionSuccess = ($epo !== null && $isNumericId && $updatedSpo?->state === SupplierPurchaseOrder::STATE_AWAITING_MANUAL_PAYMENT);

// 9. Execute Directed Read-After-Write Verification (Wait 10 seconds)
$readbackResult = null;
$readbackSuccess = false;

if ($submissionSuccess) {
    sleep(10);
    try {
        $readbackSnapshot = $gateway->getOrder((string)$externalOrderId);
        $readbackResult = [
            'external_order_id' => $readbackSnapshot->externalOrderId,
            'raw_status' => $readbackSnapshot->rawStatus,
            'order_status' => $readbackSnapshot->orderStatus,
            'tracking_number' => $readbackSnapshot->trackingNumber,
            'carrier_name' => $readbackSnapshot->carrierName,
        ];
        
        if ($readbackSnapshot->externalOrderId === (string)$externalOrderId && in_array($readbackSnapshot->rawStatus, ['WAIT_BUYER_PAY', 'PLACE_ORDER_SUCCESS', 'IN_CANCEL', 'PAYMENT_PENDING', 'WAIT_SELLER_SEND_GOODS'], true)) {
            $readbackSuccess = true;
        }
    } catch (\\Throwable $e) {
        $readbackResult = ['error' => $e->getMessage()];
    }
}

// 10. Baseline Counts After Execution
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

$finalRuling = ($submissionSuccess && $readbackSuccess)
    ? 'OFFICIAL_UNPAID_ORDER_VERIFIED'
    : ($submissionSuccess ? 'OFFICIAL_UNPAID_ORDER_AWAITING_MANUAL_READBACK' : 'SUBMISSION_FAILED_NO_EXTERNAL_ORDER');

echo json_encode([
    'ruling' => $finalRuling,
    'timestamp' => date('Y-m-d H:i:s P'),
    'git_sha' => $gitSha,
    'git_clean' => true,
    'spo_id' => $spo->id,
    'spo_number' => $spo->purchase_order_number,
    'spo_state_before' => 'ready_to_submit',
    'spo_state_after' => $updatedSpo?->state,
    'spo_payment_state' => $updatedSpo?->payment_state,
    'submission_success' => $submissionSuccess,
    'external_order_id' => $externalOrderId,
    'provider_request_id' => $epo?->provider_request_id,
    'epo_status' => $epo?->raw_status,
    'auth_summary' => $authSummary,
    'preflight_total_minor' => $totalMinor,
    'preflight_total_formatted' => number_format($totalMinor / 100, 2, '.', ''),
    'ceiling_minor' => 3215,
    'readback_success' => $readbackSuccess,
    'readback_result' => $readbackResult,
    'submission_exception' => $submissionException,
    'db_deltas' => $deltas,
    'spo35_unchanged' => $spo35Unchanged,
    'epo26_unchanged' => $epo26Unchanged,
], JSON_PRETTY_PRINT);
?>""";

    # Upload and execute
    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/execute_spo36_live_creation.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(script_php)
    sftp.close()
    
    print("[SSH] Uploaded creation script. Executing authorized single unpaid creation on Staging...")
    cmd = f"cd {remote_base} && php scripts/execute_spo36_live_creation.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Staging Creation Output ---\n{out}")
    if err:
        print(f"\n--- Staging STDERR ---\n{err}")
        
    client.close()
    
    # Save output locally
    try:
        data = json.loads(out)
        with open('scripts/spo36_live_creation_result.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("\n[Result] Saved to scripts/spo36_live_creation_result.json")
    except Exception as e:
        print(f"[Result] Could not parse JSON output: {e}")

if __name__ == '__main__':
    main()
