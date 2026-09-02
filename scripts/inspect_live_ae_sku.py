import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Illuminate\Support\Facades\DB;

$productId = '1005011942764190';
$targetSkuId = '12000059778048925';

$apiClient = app(AliExpressApiClient::class);
$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);

echo "=== Calling aliexpress.ds.product.get for Product: {$productId} ===\n";
$res = $apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
    'product_id' => $productId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

echo "HTTP OK: " . ($res['ok'] ? 'yes' : 'no') . "\n";
echo "Response Body:\n";
$body = $res['body'] ?? [];
$resp = $body['aliexpress_ds_product_get_response'] ?? $body;
$result = $resp['result'] ?? [];

$skus = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($skus['sku_id'])) {
    $skus = [$skus];
}

echo "Found " . count($skus) . " SKUs on AliExpress:\n";
$found = false;
foreach ($skus as $idx => $s) {
    $skuId = (string)($s['sku_id'] ?? '');
    $skuAttr = (string)($s['sku_attr'] ?? '');
    $price = (string)($s['offer_sale_price'] ?? $s['sku_price'] ?? '');
    $stock = (string)($s['sku_available_stock'] ?? $s['ipm_sku_stock'] ?? '');
    $isTarget = ($skuId === $targetSkuId) ? " [MATCHED TARGET SKU]" : "";
    echo "SKU #{$idx}: sku_id={$skuId}, sku_attr='{$skuAttr}', price={$price}, stock={$stock}{$isTarget}\n";
    if ($skuId === $targetSkuId) {
        $found = true;
        echo "--> Target SKU details: " . json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

if (!$found) {
    echo "\n⚠️ TARGET SKU {$targetSkuId} WAS NOT FOUND IN ALIEXPRESS ACTIVE SKUs FOR PRODUCT {$productId}!\n";
}

// Also check what payload we sent in order.create:
echo "\n=== Let's inspect what param_place_order_request4_open_api_d_t_o expects ===\n";
// Let's test calling ds.order.create with sku_attr populated if it was empty, or with exact sku format
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_ae_sku.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_ae_sku.php && rm inspect_ae_sku.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
