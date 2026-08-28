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

echo "=== PROBE 1: Try aliexpress.logistics.buyer.freight.get to query SA delivery ===\n";
$res1 = $apiClient->call('aliexpress.logistics.buyer.freight.get', $accessToken, [
    'country_code' => 'SA',
    'province_code' => 'SA',
    'city_code' => 'Riyadh',
]);
echo "Result: " . json_encode($res1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PROBE 2: Try aliexpress.ds.member.query (address info) ===\n";
$res2 = $apiClient->call('aliexpress.ds.member.query', $accessToken, []);
echo "Result: " . json_encode($res2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PROBE 3: Try aliexpress.logistics.ds.trackinginfo.query for SA region tree ===\n";
$res3 = $apiClient->call('aliexpress.logistics.redefining.getlogisticsselleraddresses', $accessToken, [
    'seller_address_query' => 'getdefault',
]);
echo "Result: " . json_encode($res3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PROBE 4: Try aliexpress.trade.redefining.findorderbyid for old successful order ===\n";
$res4 = $apiClient->call('aliexpress.trade.ds.order.get', $accessToken, [
    'single_order_query' => json_encode(['order_id' => '1122551197631333']),
]);
echo "Result (address fields from successful UAE order): ";
$body4 = $res4['body'] ?? [];
$resp4 = $body4['aliexpress_trade_ds_order_get_response'] ?? $body4;
$result4 = $resp4['result'] ?? [];
// Show logistics address used
echo json_encode([
    'shipping_address' => $result4['receipt_address'] ?? $result4['logistics_address'] ?? 'NOT_FOUND',
    'order_status' => $result4['order_status'] ?? 'UNKNOWN',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== PROBE 5: Try direct order with address2=RMAD3455, zip=14512 (5-digit standard zip) ===\n";
$productId = '1005010737996063';
$skuId = '12000053357140815';

// First get sku_attr
$prodRes = $apiClient->call('aliexpress.ds.product.get', $accessToken, [
    'product_id' => $productId,
    'ship_to_country' => 'SA',
    'target_currency' => 'USD',
    'target_language' => 'en',
]);
$prodBody = $prodRes['body'];
$prodResp = $prodBody['aliexpress_ds_product_get_response'] ?? $prodBody;
$prodResult = $prodResp['result'] ?? [];
$variants = $prodResult['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
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
$freightBody = $freightRes['body']['aliexpress_ds_freight_query_response'] ?? $freightRes['body'] ?? [];
$options = data_get($freightBody, 'result.delivery_options.delivery_option_d_t_o', []);
if (isset($options['service_name'])) $options = [$options];
$shippingService = $options[0]['service_name'] ?? 'CAINIAO_FULFILLMENT_STD';
echo "Shipping service: {$shippingService}\n\n";

// Try Permutation A: zip=14512 + address2=RMAD3455 (standard zip + national code separately)
$correlationA = 'SA-ZIP5-' . time();
$paramsA = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => $correlationA,
        'logistics_address' => [
            'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'phone_country' => '966',
            'phone_num' => '572124578',
            'mobile_no' => '572124578',
            'address' => 'حي العزيزية, الرياض, المملكة العربية السعودية',
            'address2' => 'RMAD3455',
            'city' => 'الرياض',
            'province' => 'منطقة الرياض',
            'zip' => '14512',
            'country' => 'SA',
        ],
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
echo "Submitting Permutation A (zip=14512, address2=RMAD3455)...\n";
$resA = $apiClient->call('aliexpress.ds.order.create', $accessToken, $paramsA);
$bodyA = $resA['body'] ?? [];
$respA = $bodyA['aliexpress_ds_order_create_response'] ?? $bodyA;
$resultA = $respA['result'] ?? [];
$errA = $bodyA['error_response'] ?? [];
echo "Response A: " . json_encode([
    'is_success' => $resultA['is_success'] ?? false,
    'error_code' => $resultA['error_code'] ?? ($errA['code'] ?? null),
    'error_msg' => $resultA['error_msg'] ?? ($errA['msg'] ?? null),
    'order_list' => $resultA['order_list'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Try Permutation B: English address
$correlationB = 'SA-EN-' . time();
$paramsB = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => $correlationB,
        'logistics_address' => [
            'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'phone_country' => '966',
            'phone_num' => '572124578',
            'mobile_no' => '572124578',
            'address' => 'Al Aziziyah District, Riyadh',
            'address2' => '3455',
            'city' => 'Riyadh',
            'province' => 'Riyadh Region',
            'zip' => 'RMAD3455',
            'country' => 'SA',
        ],
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
echo "Submitting Permutation B (English address, zip=RMAD3455)...\n";
$resB = $apiClient->call('aliexpress.ds.order.create', $accessToken, $paramsB);
$bodyB = $resB['body'] ?? [];
$respB = $bodyB['aliexpress_ds_order_create_response'] ?? $bodyB;
$resultB = $respB['result'] ?? [];
$errB = $bodyB['error_response'] ?? [];
echo "Response B: " . json_encode([
    'is_success' => $resultB['is_success'] ?? false,
    'error_code' => $resultB['error_code'] ?? ($errB['code'] ?? null),
    'error_msg' => $resultB['error_msg'] ?? ($errB['msg'] ?? null),
    'order_list' => $resultB['order_list'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Try Permutation C: Exact match with AliExpress saved address format 
$correlationC = 'SA-EXACT-' . time();
$paramsC = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => $correlationC,
        'logistics_address' => [
            'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
            'phone_country' => '+966',
            'phone_num' => '0572124578',
            'mobile_no' => '0572124578',
            'address' => 'حي العزيزية, الرياض, المملكة العربية السعودية',
            'address2' => '3455',
            'city' => 'الرياض',
            'province' => 'منطقة الرياض',
            'zip' => 'RMAD3455',
            'country' => 'SA',
            'locale' => 'ar_SA',
        ],
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
echo "Submitting Permutation C (Arabic, zip=RMAD3455, address2=3455)...\n";
$resC = $apiClient->call('aliexpress.ds.order.create', $accessToken, $paramsC);
$bodyC = $resC['body'] ?? [];
$respC = $bodyC['aliexpress_ds_order_create_response'] ?? $bodyC;
$resultC = $respC['result'] ?? [];
$errC = $bodyC['error_response'] ?? [];
echo "Response C: " . json_encode([
    'is_success' => $resultC['is_success'] ?? false,
    'error_code' => $resultC['error_code'] ?? ($errC['code'] ?? null),
    'error_msg' => $resultC['error_msg'] ?? ($errC['msg'] ?? null),
    'order_list' => $resultC['order_list'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== DONE ===\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_sa_address_v3.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_sa_address_v3.php && rm probe_sa_address_v3.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
