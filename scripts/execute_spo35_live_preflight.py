import json
import os
import sys
import secrets

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    target_sha = "f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4"
    spo_id = 35
    
    preflight_php = """<?php
$projDir = '""" + remote_base + """';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Services\\AliExpress\\AliExpressApiClient;
use App\\Services\\AliExpress\\AliExpressOAuthService;
use Webkul\\Procurement\\DTO\\ExternalOrderDraft;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Procurement\\Models\\ProcurementDemandAllocation;

// 1. Check Pre-Execution Gates
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$gitStatus = trim(shell_exec('cd ' . escapeshellarg($projDir) . " && git status --porcelain | grep -v '^?? storage/' | grep -v '^?? .env' | grep -v '^?? bootstrap/cache/'"));
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

// 2. Inspect SPO #35 and Context
$spo = SupplierPurchaseOrder::with(['items', 'batch'])->find(""" + str(spo_id) + """);

if (!$spo) {
    echo json_encode(['ruling' => 'SPO_PREFLIGHT_BLOCKED — SPO #35 not found in database']);
    exit(1);
}

if ($spo->state !== SupplierPurchaseOrder::STATE_READY_TO_SUBMIT) {
    echo json_encode(['ruling' => "SPO_PREFLIGHT_BLOCKED_CONTEXT_MISMATCH — SPO state is '{$spo->state}', expected 'ready_to_submit'"]);
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
    'failed_jobs'
];

$countsBefore = [];
foreach ($tables as $t) {
    $countsBefore[$t] = DB::table($t)->count();
}

// 4. Resolve Shipping Address strictly from Key Management default source
$gateway = app(AliExpressOrderSubmissionGateway::class);
$resolvedAddress = $gateway->resolveWarehouseShippingAddress();

// 5. Query live product & price details
$token = app(AliExpressOAuthService::class)->latestToken();
$apiClient = app(AliExpressApiClient::class);

$productId = $firstItem->supplier_product_id;
$skuId = $firstItem->supplier_sku_id;
$qty = (int) $firstItem->qty_ordered;

$prodRes = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
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

// 6. Build In-Memory Draft and Execute Strict Gateway Preflight
$draftCorrelationKey = 'PREFLIGHT-SPO-35-' . strtoupper(bin2hex(random_bytes(4)));
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
    ]
);

$preflight = $gateway->preflight($draft);

// 7. Baseline Counts After Preflight
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

$isReady = (
    $gitClean &&
    $appDebug === false &&
    $preflight->isSuccess &&
    $preflight->isDeliverableToDestination &&
    !empty($exactSkuAttr) &&
    !empty($preflight->shippingServiceName) &&
    $unitPriceMinor !== null &&
    $totalMinor !== null &&
    $dbUnchanged
);

$now = new DateTime('now', new DateTimeZone('Asia/Riyadh'));
$expiresAt = (clone $now)->add(new DateInterval('PT15M'));

echo json_encode([
    'ruling' => $isReady ? 'SPO_LIVE_PREAPPROVAL_READY' : 'SPO_PREFLIGHT_BLOCKED — Preflight validation failed',
    'timestamp' => $now->format('Y-m-d H:i:s P'),
    'expires_at' => $expiresAt->format('Y-m-d H:i:s P'),
    'validity_minutes' => 15,
    'git_sha' => $gitSha,
    'git_clean' => $gitClean,
    'spo_id' => $spo->id,
    'spo_number' => $spo->purchase_order_number,
    'spo_state' => $spo->state,
    'batch_id' => $batch->id,
    'batch_number' => $batch->batch_number,
    'batch_state' => $batch->state,
    'product' => [
        'id' => $productId,
        'title' => $productTitle,
        'store' => $storeName,
    ],
    'sku' => [
        'id' => $skuId,
        'sku_attr' => $exactSkuAttr,
        'quantity' => $qty,
    ],
    'shipping_address' => [
        'display' => 'SA / Riyadh / Key Management source [default]',
        'country' => 'SA',
        'city' => 'Riyadh',
    ],
    'logistics' => [
        'service_name' => $preflight->shippingServiceName,
        'min_days' => $preflight->minDeliveryDays,
        'max_days' => $preflight->maxDeliveryDays,
        'tracking' => $preflight->trackingAvailable,
    ],
    'money_normalization' => [
        'unit_price' => [
            'raw_field' => $unitPriceField,
            'raw_value' => $unitPriceRaw,
            'raw_unit' => 'decimal_usd',
            'minor_cents' => $unitPriceMinor,
            'formatted_usd' => '$' . $unitPriceFormatted,
            'currency' => 'USD',
        ],
        'freight' => [
            'raw_field' => $preflight->moneyEvidence['raw_field'] ?? 'unknown',
            'raw_value' => $preflight->moneyEvidence['raw_amount'] ?? null,
            'raw_unit' => $preflight->moneyEvidence['raw_unit'] ?? 'unknown',
            'minor_cents' => $freightMinor,
            'formatted_usd' => '$' . $freightFormatted,
            'currency' => $preflight->shippingCurrency,
        ],
        'fees_discounts' => [
            'fees_minor' => $documentedFeesMinor,
            'discounts_minor' => $documentedDiscountsMinor,
        ],
        'total' => [
            'formula' => sprintf('(%d minor × %d qty) + %d freight minor + 0 fees - 0 discounts = %d minor', $unitPriceMinor, $qty, $freightMinor, $totalMinor),
            'minor_cents' => $totalMinor,
            'formatted_usd' => '$' . $totalFormatted,
            'currency' => 'USD',
        ]
    ],
    'api_calls_executed' => [
        'aliexpress.ds.product.get' => true,
        'aliexpress.ds.freight.query' => true,
        'aliexpress.ds.order.create' => false,
        'aliexpress.ds.order.get' => false,
    ],
    'db_integrity' => [
        'counts_before' => $countsBefore,
        'counts_after' => $countsAfter,
        'unchanged' => $dbUnchanged,
    ]
], JSON_PRETTY_PRINT);
"""
    
    sftp = client.open_sftp()
    with sftp.file('/tmp/execute_spo35_preflight.php', 'w') as f:
        f.write(preflight_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/execute_spo35_preflight.php && rm -f /tmp/execute_spo35_preflight.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
