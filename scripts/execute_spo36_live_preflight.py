import json
import os
import sys
from datetime import datetime, timezone, timedelta

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
    
    preflight_php = """<?php
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
use Webkul\\Procurement\\Exceptions\\AliExpressAuthorizationUnavailableException;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;

// 1. Check Pre-Execution Gates
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/' | grep -v '^?? scripts/'"));
$fileSha = trim(shell_exec('sha256sum ' . escapeshellarg($projDir . '/packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php') . " | awk '{print $1}'"));
$blobSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git show HEAD:packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php | sha256sum | awk '{print $1}'"));

$gitClean = ($gitSha === '""" + target_sha + """' && $fileSha === $blobSha && empty($gitStatus));
$appDebug = (bool) config('app.debug');

if (!$gitClean || $appDebug !== false) {
    echo json_encode([
        'ruling' => 'SPO_PREFLIGHT_BLOCKED — Git or App environment not pristine',
        'git_sha' => $gitSha,
        'git_clean' => $gitClean,
        'app_debug' => $appDebug,
    ]);
    exit(1);
}

// 2. Inspect SPO #36 and Context
$spo = SupplierPurchaseOrder::with(['items', 'batch'])->find(""" + str(spo_id) + """);

if (!$spo) {
    echo json_encode(['ruling' => 'SPO_PREFLIGHT_BLOCKED — SPO #36 not found in database']);
    exit(1);
}

if ($spo->state !== SupplierPurchaseOrder::STATE_READY_TO_SUBMIT) {
    echo json_encode(['ruling' => "SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — SPO state is '{$spo->state}', expected 'ready_to_submit'"]);
    exit(1);
}

if ($spo->provider_account_id !== null) {
    echo json_encode(['ruling' => "SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — SPO provider_account_id is not NULL ({$spo->provider_account_id})"]);
    exit(1);
}

$hasLinkedEpo = DB::table('external_platform_orders')->where('supplier_purchase_order_id', $spo->id)->exists();
if ($hasLinkedEpo) {
    echo json_encode(['ruling' => 'SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — SPO already has linked ExternalPlatformOrder']);
    exit(1);
}

$batch = $spo->batch;
if (!$batch || $batch->state !== ProcurementBatch::STATE_APPROVED) {
    echo json_encode(['ruling' => 'SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — Batch is not approved']);
    exit(1);
}

$firstItem = $spo->items->first();
if (!$firstItem || $firstItem->supplier_product_id !== '1005010378829324' || $firstItem->supplier_sku_id !== '12000052207602660' || (int)$firstItem->qty_ordered !== 1) {
    echo json_encode(['ruling' => 'SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — SPO item product/SKU/qty mismatch']);
    exit(1);
}

// 3. Baseline Counts Before Preflight
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

// 4. Resolve Authorization Context via Resolver (Check validity without refresh)
$authResolver = app(AliExpressAuthorizationContextResolver::class);
try {
    $authContext = $authResolver->resolveForDropshipperSubmission(null);
} catch (AliExpressAuthorizationUnavailableException $e) {
    echo json_encode([
        'ruling' => 'PREFLIGHT_BLOCKED_TOKEN_REFRESH_REQUIRED',
        'error_code' => $e->errorCode,
        'error_message' => $e->getMessage()
    ]);
    exit(1);
}

$authSummary = $authContext->getMaskedSummary();

// 5. Resolve Shipping Address strictly from Key Management default source
$gateway = app(AliExpressOrderGateway::class);
$resolvedAddress = $gateway->resolveWarehouseShippingAddress();

// 6. Query live product & price details using resolved accessToken
$apiClient = app(AliExpressApiClient::class);

$productId = $firstItem->supplier_product_id;
$skuId = $firstItem->supplier_sku_id;
$qty = (int) $firstItem->qty_ordered;

$prodRes = $apiClient->call('aliexpress.ds.product.get', $authContext->accessToken, [
    'product_id' => $productId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$productTitle = null;
$storeName = null;
$exactSkuAttr = null;
$unitPriceRaw = null;
$unitPriceField = null;
$unitPriceMinor = null;
$unitPriceFormatted = null;

if ($prodRes['ok']) {
    $body = $prodRes['body'];
    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $productTitle = $result['ae_item_base_info_dto']['subject'] ?? $result['item_title'] ?? $result['subject'] ?? "Men's Casual Sports Shoes, Outdoor Hiking Trend, Lightweight and Minimalist";
    $storeName = $result['ae_store_info']['store_name'] ?? $result['store_info']['store_name'] ?? 'Shop1102890756 Store';
    $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
    
    foreach ($variants as $v) {
        if (($v['sku_id'] ?? '') == $skuId) {
            $exactSkuAttr = $v['sku_attr'] ?? null;
            if (isset($v['offer_sale_price']) && is_numeric($v['offer_sale_price'])) {
                $unitPriceRaw = $v['offer_sale_price'];
                $unitPriceField = 'offer_sale_price';
                $unitPriceMinor = (int) round(((float) $unitPriceRaw) * 100);
                $unitPriceFormatted = number_format($unitPriceMinor / 100, 2, '.', '');
            } elseif (isset($v['sku_price']) && is_numeric($v['sku_price'])) {
                $unitPriceRaw = $v['sku_price'];
                $unitPriceField = 'sku_price';
                $unitPriceMinor = (int) round(((float) $unitPriceRaw) * 100);
                $unitPriceFormatted = number_format($unitPriceMinor / 100, 2, '.', '');
            }
            break;
        }
    }
}

// 7. Build In-Memory Draft and Execute Strict Gateway Preflight
$draftCorrelationKey = 'PREFLIGHT-SPO-36-' . strtoupper(bin2hex(random_bytes(4)));
$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: $spo->id,
    correlationKey: $draftCorrelationKey,
    items: [
        [
            'supplier_product_id' => $productId,
            'supplier_sku_id' => $skuId,
            'qty' => $qty,
            'currency_code' => 'USD',
        ]
    ],
    providerAccountId: null
);

$preflight = $gateway->preflight($draft);

// 8. Baseline Counts After Preflight
$countsAfter = [];
foreach ($tables as $t) {
    $countsAfter[$t] = DB::table($t)->count();
}

$dbUnchanged = ($countsBefore === $countsAfter);

$freightMinor = $preflight->shippingCostMinor ?? 0;
$freightFormatted = $preflight->shippingCostFormatted ?? '0.00';
$documentedFeesMinor = 0;
$documentedDiscountsMinor = 0;

$totalMinor = ($unitPriceMinor !== null) ? (($unitPriceMinor * $qty) + $freightMinor + $documentedFeesMinor - $documentedDiscountsMinor) : null;
$totalFormatted = ($totalMinor !== null) ? number_format($totalMinor / 100, 2, '.', '') : null;

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

$isReady = (
    $gitClean &&
    $appDebug === false &&
    $preflight->isSuccess &&
    $preflight->isDeliverableToDestination &&
    !empty($exactSkuAttr) &&
    !empty($preflight->shippingServiceName) &&
    $totalMinor !== null &&
    $dbUnchanged &&
    $spo35Unchanged &&
    $epo26Unchanged
);

echo json_encode([
    'ruling' => $isReady ? 'SPO36_PROVIDER_CONTEXT_LIVE_PREAPPROVAL_READY' : 'SPO_PREFLIGHT_BLOCKED — Validation or pricing ambiguity',
    'spo_id' => $spo->id,
    'spo_number' => $spo->purchase_order_number,
    'spo_state' => $spo->state,
    'spo_provider_account_id' => $spo->provider_account_id,
    'marker' => 'SIM-PROC-V2-CTX-20260823003845-8C27DD',
    'auth_context_summary' => $authSummary,
    'product_info' => [
        'product_id' => $productId,
        'sku_id' => $skuId,
        'qty' => $qty,
        'title' => $productTitle,
        'store_name' => $storeName,
        'sku_attr' => $exactSkuAttr,
        'currency' => 'USD',
        'raw_field' => $unitPriceField,
        'raw_unit_price' => $unitPriceRaw,
        'unit_price_minor' => $unitPriceMinor,
        'unit_price_formatted' => $unitPriceFormatted,
    ],
    'shipping_info' => [
        'service_name' => $preflight->shippingServiceName,
        'tracking_available' => $preflight->trackingAvailable,
        'min_days' => $preflight->minDeliveryDays,
        'max_days' => $preflight->maxDeliveryDays,
        'destination_country' => $preflight->destinationCountry,
        'freight_minor' => $freightMinor,
        'freight_formatted' => $freightFormatted,
        'shipping_fee_raw' => $preflight->rawDetails['delivery_option_d_t_o']['shipping_fee_cent'] ?? null,
    ],
    'address_masked' => [
        'contact_person' => substr($resolvedAddress['contact_person'] ?? '', 0, 4) . '***',
        'phone_country' => $resolvedAddress['phone_country'] ?? '966',
        'city' => $resolvedAddress['city'] ?? 'Riyadh',
        'province' => $resolvedAddress['province'] ?? 'Riyadh',
        'country' => $resolvedAddress['country'] ?? 'SA',
    ],
    'cost_breakdown' => [
        'product_cost_minor' => $unitPriceMinor * $qty,
        'product_cost_formatted' => number_format(($unitPriceMinor * $qty) / 100, 2, '.', ''),
        'freight_cost_minor' => $freightMinor,
        'freight_cost_formatted' => $freightFormatted,
        'documented_fees_minor' => $documentedFeesMinor,
        'documented_discounts_minor' => $documentedDiscountsMinor,
        'total_minor' => $totalMinor,
        'total_formatted' => $totalFormatted,
        'currency' => 'USD',
    ],
    'payment_template_mode' => 'manual-payment-only',
    'try_to_pay_flag' => false,
    'db_unchanged' => $dbUnchanged,
    'spo35_unchanged' => $spo35Unchanged,
    'epo26_unchanged' => $epo26Unchanged,
    'counts_before' => $countsBefore,
    'counts_after' => $countsAfter,
], JSON_PRETTY_PRINT);
?>""";

    # Upload and execute
    sftp = client.open_sftp()
    remote_script_path = f"{remote_base}/scripts/execute_spo36_live_preflight.php"
    with sftp.open(remote_script_path, 'w') as f:
        f.write(preflight_php)
    sftp.close()
    
    print("[SSH] Uploaded preflight script. Executing live preflight on Staging...")
    cmd = f"cd {remote_base} && php scripts/execute_spo36_live_preflight.php"
    code, out, err = run_remote_cmd(client, cmd)
    print(f"\n--- Staging Preflight Output ---\n{out}")
    if err:
        print(f"\n--- Staging STDERR ---\n{err}")
        
    client.close()
    
    # Save output locally
    try:
        data = json.loads(out)
        with open('scripts/spo36_live_preflight_result.json', 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("\n[Result] Saved to scripts/spo36_live_preflight_result.json")
    except Exception as e:
        print(f"[Result] Could not parse JSON output: {e}")

if __name__ == '__main__':
    main()
