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
echo "=== LATEST TOKEN INFO ===\n";
echo "Token ID: {$token->id}, Account: {$token->account}, Created: {$token->created_at}\n";
$accessToken = $token->access_token;
$apiClient = app(AliExpressApiClient::class);

$productId = '1005010737996063';
$skuId = '12000053357140815';

// 1. Resolve product & sku_attr
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

// 2. Resolve freight
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

// 3. Test Saudi Address Permutations with fresh Online Token
$tests = [
    'SPL_Arabic_zip_RMAD' => [
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
    ],
    'SPL_English_zip_RMAD' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah Dist',
        'address2' => '7664',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => 'RMAD3455',
        'country' => 'SA',
    ],
    'SPL_zip14512_passport_RMAD' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'address' => '3455 أحمد بن رشد, حي العزيزية',
        'address2' => 'RMAD3455',
        'city' => 'الرياض',
        'province' => 'منطقة الرياض',
        'zip' => '14512',
        'passport_no' => 'RMAD3455',
        'country' => 'SA',
    ],
];

foreach ($tests as $name => $addr) {
    echo "=== SUBMITTING TEST: {$name} ===\n";
    $correlation = 'LIVE-ONLINE-' . time() . '-' . substr(md5($name), 0, 4);
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

    $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
    $body = $res['body'] ?? [];
    $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $err = $body['error_response'] ?? [];

    $isSuccess = $result['is_success'] ?? false;
    $errCode = $result['error_code'] ?? ($err['code'] ?? null);
    $errMsg = $result['error_msg'] ?? ($err['msg'] ?? null);
    $orders = $result['order_list'] ?? null;

    if ($isSuccess) {
        echo "🎉🎉🎉 ORDER PLACED SUCCESSFULLY ON ALIEXPRESS! 🎉🎉🎉\n";
        echo "Official AliExpress Order List: " . json_encode($orders) . "\n";
        echo "Winning Format: {$name}\n";
        print_r($addr);
        break;
    } else {
        echo "❌ {$errCode}: {$errMsg}\n\n";
    }
    usleep(500000);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_live_online_order.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_live_online_order.php && rm test_live_online_order.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
