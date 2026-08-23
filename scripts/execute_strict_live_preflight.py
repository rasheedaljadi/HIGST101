import json
import os
import sys
import secrets

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from scripts.remote_ssh_helper import get_ssh_client, run_remote_cmd

def main():
    client = get_ssh_client()
    remote_base = "/home/highest-ye/htdocs/highest-ye.store"
    
    # Generate fresh in-memory draft key
    fresh_suffix = secrets.token_hex(4).upper()
    draft_key = f"DRAFT-SIM-SA-{fresh_suffix}"
    
    preflight_php = f"""<?php
$projDir = '{remote_base}';
require $projDir . '/vendor/autoload.php';
$app = require_once $projDir . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use App\\Models\\AliExpressToken;
use App\\Services\\AliExpress\\AliExpressApiClient;
use Webkul\\Procurement\\DTO\\ExternalOrderDraft;
use Webkul\\Procurement\\Gateways\\AliExpressOrderSubmissionGateway;
use Webkul\\Procurement\\Support\\AliExpressMoneyNormalizer;

// 1. Check Git SHA and APP_DEBUG
$gitSha = trim(shell_exec('cd ' . escapeshellarg($projDir) . ' && git rev-parse HEAD'));
$appDebug = (bool) config('app.debug');

$tables = [
    'external_platform_orders',
    'supplier_purchase_orders',
    'procurement_batches',
    'procurement_demands',
    'procurement_demand_allocations',
    'procurement_cost_snapshots',
    'inventory_sources',
    'aliexpress_webhook_inbox_messages',
    'orders',
    'invoices',
    'shipments',
    'refunds',
    'jobs',
    'failed_jobs'
];

$countsBefore = [];
foreach ($tables as $t) {{
    $countsBefore[$t] = DB::table($t)->count();
}}

// 2. Resolve default Key Management address strictly
$gateway = app(AliExpressOrderSubmissionGateway::class);
$addressConfigured = false;
$resolvedAddress = null;
$addressError = null;

try {{
    $resolvedAddress = $gateway->resolveWarehouseShippingAddress();
    $addressConfigured = true;
}} catch (\\Throwable $e) {{
    $addressError = $e->getMessage();
}}

// 3. Define candidate product and SKU (Active verified AliExpress item)
$productId = '1005010378829324';
$skuId = '12000052207602660'; // White / Size 39
$qty = 1;
$draftKey = '{draft_key}';

$draft = new ExternalOrderDraft(
    supplierPurchaseOrderId: 999999,
    correlationKey: $draftKey,
    items: [
        [
            'supplier_product_id' => $productId,
            'supplier_sku_id' => $skuId,
            'qty' => $qty,
            'currency_code' => 'USD',
        ]
    ]
);

// 4. Query live product info via API Client directly to extract authoritative price and title
$token = app(\\App\\Services\\AliExpress\\AliExpressOAuthService::class)->latestToken();
$apiClient = app(AliExpressApiClient::class);

$productCallOk = false;
$unitPriceRaw = null;
$unitPriceField = null;
$unitPriceMinor = null;
$unitPriceFormatted = null;
$productTitle = null;
$storeName = null;
$exactSkuAttr = null;

$prodRes = $apiClient->call('aliexpress.ds.product.get', $token->access_token, [
    'product_id' => $productId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

if ($prodRes['ok']) {{
    $productCallOk = true;
    $body = $prodRes['body'];
    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $productTitle = $result['ae_item_base_info_dto']['subject'] ?? $result['item_title'] ?? $result['subject'] ?? 'Men Shoes Casual Breathable High-Top Shoes';
    $storeName = $result['ae_store_info']['store_name'] ?? $result['store_info']['store_name'] ?? 'Shop1103028328 Store';
    $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
    
    foreach ($variants as $v) {{
        if (($v['sku_id'] ?? '') == $skuId) {{
            $exactSkuAttr = $v['sku_attr'] ?? null;
            if (isset($v['offer_sale_price']) && is_numeric($v['offer_sale_price'])) {{
                $unitPriceRaw = $v['offer_sale_price'];
                $unitPriceField = 'offer_sale_price';
                $unitPriceMinor = (int) round(((float) $unitPriceRaw) * 100);
                $unitPriceFormatted = number_format($unitPriceMinor / 100, 2, '.', '');
            }} elseif (isset($v['sku_price']) && is_numeric($v['sku_price'])) {{
                $unitPriceRaw = $v['sku_price'];
                $unitPriceField = 'sku_price';
                $unitPriceMinor = (int) round(((float) $unitPriceRaw) * 100);
                $unitPriceFormatted = number_format($unitPriceMinor / 100, 2, '.', '');
            }}
            break;
        }}
    }}
}}

// 5. Execute Strict Gateway Preflight
$preflight = $gateway->preflight($draft);

// 6. Check Database Invariance
$countsAfter = [];
foreach ($tables as $t) {{
    $countsAfter[$t] = DB::table($t)->count();
}}

$dbUnchanged = ($countsBefore === $countsAfter);

// Calculations in Integer Minor Units (Cents)
$freightMinor = $preflight->shippingCostMinor ?? 0;
$freightFormatted = $preflight->shippingCostFormatted ?? '0.00';
$documentedFeesMinor = 0;
$documentedDiscountsMinor = 0;

$totalMinor = ($unitPriceMinor !== null) ? (($unitPriceMinor * $qty) + $freightMinor + $documentedFeesMinor - $documentedDiscountsMinor) : null;
$totalFormatted = ($totalMinor !== null) ? number_format($totalMinor / 100, 2, '.', '') : null;

$isReady = (
    $appDebug === false &&
    $addressConfigured &&
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
    'ruling' => $isReady ? 'STRICT_LIVE_PREAPPROVAL_READY' : 'STRICT_PREAPPROVAL_BLOCKED',
    'timestamp' => $now->format('Y-m-d H:i:s P'),
    'expires_at' => $expiresAt->format('Y-m-d H:i:s P'),
    'validity_minutes' => 15,
    'git_sha' => $gitSha,
    'app_debug' => $appDebug,
    'draft_reference' => $draftKey,
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
        'configured' => $addressConfigured,
        'error' => $addressError,
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
    'future_order_creation_template' => [
        'try_to_pay' => false,
        'mode' => 'manual-payment-only',
        'order_type' => 'UNPAID_ONLY',
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
    with sftp.file('/tmp/execute_strict_live_preflight.php', 'w') as f:
        f.write(preflight_php)
    sftp.close()
    
    code, out, err = run_remote_cmd(client, "php /tmp/execute_strict_live_preflight.php && rm -f /tmp/execute_strict_live_preflight.php")
    print(out)
    if err:
        print("ERR:", err)
        
    client.close()

if __name__ == '__main__':
    main()
