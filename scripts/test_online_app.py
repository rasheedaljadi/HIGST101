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

echo "=== TESTING WITH ONLINE APP STATUS ===\n";
echo "Token ID: {$token->id}, Seller ID: {$token->seller_id}\n";

$productId = '1005010737996063';
$skuId = '12000053357140815';

// Resolve sku_attr
$prodRes = $apiClient->call('aliexpress.ds.product.get', $accessToken, [
    'product_id' => $productId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);

$variants = data_get($prodRes, 'body.aliexpress_ds_product_get_response.result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
if (isset($variants['sku_id'])) $variants = [$variants];
$skuAttr = '';
foreach ($variants as $v) {
    if (($v['sku_id'] ?? '') == $skuId) {
        $skuAttr = $v['sku_attr'] ?? '';
        break;
    }
}
echo "Resolved sku_attr: {$skuAttr}\n";

// Get freight
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $accessToken, [
    'queryDeliveryReq' => [
        'productId' => $productId,
        'shipToCountry' => 'SA',
        'quantity' => 1,
        'currency' => 'USD',
        'language' => 'en_US',
        'locale' => 'en_US',
        'selectedSkuId' => $skuId,
    ],
]);
$options = data_get($freightRes, 'body.aliexpress_ds_freight_query_response.result.delivery_options.delivery_option_d_t_o', []);
if (isset($options['service_name'])) $options = [$options];
$shippingService = $options[0]['service_name'] ?? 'CAINIAO_FULFILLMENT_STD';
echo "Shipping service: {$shippingService}\n\n";

// Exact SPL Address Format
$addr = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية',
    'address2' => '7664',
    'city' => 'الرياض',
    'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

$correlation = 'ONLINE-TEST-' . time();
$params = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => $correlation,
        'logistics_address' => $addr,
        'product_items' => [[
            'product_count' => 1,
            'product_id' => $productId,
            'sku_id' => $skuId,
            'sku_attr' => $skuAttr,
            'sku_define_type' => 'sku_id',
            'logistics_service_name' => $shippingService,
        ]],
    ],
];

echo "Submitting order to AliExpress with Online App Status...\n";
$res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
$body = $res['body'] ?? [];
$resp = $body['aliexpress_ds_order_create_response'] ?? $body;
$result = $resp['result'] ?? [];
$err = $body['error_response'] ?? [];

echo "Response:\n" . json_encode([
    'ok' => $res['ok'],
    'status' => $res['status'],
    'is_success' => $result['is_success'] ?? false,
    'error_code' => $result['error_code'] ?? ($err['code'] ?? null),
    'error_msg' => $result['error_msg'] ?? ($err['msg'] ?? null),
    'order_list' => $result['order_list'] ?? null,
    'full_response' => $body,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (!empty($result['is_success']) && $result['is_success'] === true) {
    echo "🎉🎉🎉 SUCCESS WITH ONLINE STATUS! 🎉🎉🎉\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_online_app.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_online_app.php && rm test_online_app.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
