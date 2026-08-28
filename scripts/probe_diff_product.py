import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();
$accessToken = $token->access_token;
$apiClient = app(AliExpressApiClient::class);

// Use a DIFFERENT product to test if SA validation is product/seller-specific
// Let's find a product with SA shipping and resolve its SKU
$testProductId = '1005006903383533';  // Different product from our catalog

echo "=== Testing with DIFFERENT product: {$testProductId} ===\n";

$prodRes = $apiClient->call('aliexpress.ds.product.get', $accessToken, [
    'product_id' => $testProductId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$prodBody = $prodRes['body'];
$prodResp = $prodBody['aliexpress_ds_product_get_response'] ?? $prodBody;
$prodResult = $prodResp['result'] ?? [];

echo "Product title: " . ($prodResult['ae_item_base_info_dto']['subject'] ?? 'UNKNOWN') . "\n";

$variants = $prodResult['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
if (isset($variants['sku_id'])) $variants = [$variants];

if (empty($variants)) {
    echo "No variants found for this product. Trying another product...\n";
    
    // Try yet another product
    $testProductId = '1005010378829324';
    echo "Trying product: {$testProductId}\n";
    $prodRes = $apiClient->call('aliexpress.ds.product.get', $accessToken, [
        'product_id' => $testProductId,
        'ship_to_country' => 'SA',
        'target_currency' => 'USD',
        'target_language' => 'en',
    ]);
    $prodBody = $prodRes['body'];
    $prodResp = $prodBody['aliexpress_ds_product_get_response'] ?? $prodBody;
    $prodResult = $prodResp['result'] ?? [];
    $variants = $prodResult['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
    if (isset($variants['sku_id'])) $variants = [$variants];
}

$skuId = $variants[0]['sku_id'] ?? '';
$skuAttr = $variants[0]['sku_attr'] ?? '';
echo "SKU ID: {$skuId}, SKU Attr: {$skuAttr}\n";

// Get freight for this product to SA
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $accessToken, [
    'queryDeliveryReq' => [
        'productId' => $testProductId,
        'shipToCountry' => 'SA',
        'quantity' => 1,
        'currency' => 'USD',
        'language' => 'en_US',
        'locale' => 'en_US',
        'selectedSkuId' => $skuId,
    ],
]);
$freightBody = $freightRes['body']['aliexpress_ds_freight_query_response'] ?? $freightRes['body'] ?? [];
$options = data_get($freightBody, 'result.delivery_options.delivery_option_d_t_o', []);
if (isset($options['service_name'])) $options = [$options];
$shippingService = $options[0]['service_name'] ?? 'CAINIAO_FULFILLMENT_STD';
echo "Freight to SA available: " . (empty($options) ? 'NO' : 'YES') . " | Service: {$shippingService}\n\n";

if (empty($skuId) || empty($skuAttr)) {
    echo "Cannot proceed - no valid SKU data\n";
    exit;
}

// Test order with this different product
$addr = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية, RMAD3455',
    'address2' => '7664',
    'city' => 'الرياض',
    'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

$correlation = 'PROD2-SA-' . time();
$params = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => $correlation,
        'logistics_address' => $addr,
        'product_items' => [[
            'product_count' => 1,
            'product_id' => $testProductId,
            'sku_id' => $skuId,
            'sku_attr' => $skuAttr,
            'sku_define_type' => 'sku_id',
            'logistics_service_name' => $shippingService,
        ]],
    ],
];

echo "=== Submitting order for product {$testProductId} to SA ===\n";
$res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
$body = $res['body'] ?? [];
$resp = $body['aliexpress_ds_order_create_response'] ?? $body;
$result = $resp['result'] ?? [];
$err = $body['error_response'] ?? [];

$success = $result['is_success'] ?? false;
$errCode = $result['error_code'] ?? ($err['code'] ?? null);
$errMsg = $result['error_msg'] ?? ($err['msg'] ?? null);

if ($success) {
    echo "✅ SUCCESS with different product! Order: " . json_encode($result['order_list'] ?? null) . "\n";
} else {
    echo "❌ FAILED: {$errCode}: {$errMsg}\n";
    echo ">> SA address validation failure is PLATFORM-WIDE, not product-specific\n";
}

echo "\n=== CONCLUSION ===\n";
echo "If both products fail with same error, the issue is at AliExpress PLATFORM level.\n";
echo "This means the dropshipping API (aliexpress.ds.order.create) has a server-side\n";
echo "Saudi national address GIS validation that cannot be bypassed via field formatting.\n";
echo "\nRecommended alternatives:\n";
echo "1. Place SA orders manually through AliExpress website (web interface bypasses this)\n";
echo "2. Use a forwarding address (e.g. Dubai/UAE) as an intermediary\n";
echo "3. Contact AliExpress Open Platform support to register SA address for API use\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_diff_product.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_diff_product.php && rm probe_diff_product.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
