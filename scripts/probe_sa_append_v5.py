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

$productId = '1005010737996063';
$skuId = '12000053357140815';

// Resolve sku_attr
$prodRes = $apiClient->call('aliexpress.ds.product.get', $accessToken, [
    'product_id' => $productId, 'ship_to_country' => 'SA',
    'target_currency' => 'USD', 'target_language' => 'en',
]);
$variants = data_get($prodRes, 'body.aliexpress_ds_product_get_response.result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
if (isset($variants['sku_id'])) $variants = [$variants];
$skuAttr = '';
foreach ($variants as $v) { if (($v['sku_id'] ?? '') == $skuId) { $skuAttr = $v['sku_attr'] ?? ''; break; } }

// Get freight
$freightRes = $apiClient->call('aliexpress.ds.freight.query', $accessToken, [
    'queryDeliveryReq' => ['productId' => $productId, 'shipToCountry' => 'SA', 'quantity' => 1, 'currency' => 'USD', 'language' => 'en_US', 'locale' => 'en_US', 'selectedSkuId' => $skuId],
]);
$options = data_get($freightRes, 'body.aliexpress_ds_freight_query_response.result.delivery_options.delivery_option_d_t_o', []);
if (isset($options['service_name'])) $options = [$options];
$shippingService = $options[0]['service_name'] ?? 'CAINIAO_FULFILLMENT_STD';
echo "sku_attr={$skuAttr}, shipping={$shippingService}\n\n";

// SPL Data:
// Short Address: RMAD3455, Building: 3455, Street: أحمد بن رشد
// Secondary: 7664, District: حي العزيزية, Postal: 14512, City: الرياض

$tests = [];

// KEY INSIGHT: Append short address code to end of address field string

// T1: Arabic address + RMAD3455 appended, zip=RMAD3455
$tests['T1_ar_append_zip_rmad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية, RMAD3455',
    'address2' => '7664',
    'city' => 'الرياض', 'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455', 'country' => 'SA',
];

// T2: English address + RMAD3455 appended, zip=RMAD3455
$tests['T2_en_append_zip_rmad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd, Al Aziziyah, RMAD3455',
    'address2' => '7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => 'RMAD3455', 'country' => 'SA',
];

// T3: Arabic, append RMAD3455, zip=14512 (5-digit postal)
$tests['T3_ar_append_zip_14512'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية, RMAD3455',
    'address2' => '7664',
    'city' => 'الرياض', 'province' => 'منطقة الرياض',
    'zip' => '14512', 'country' => 'SA',
];

// T4: English, append RMAD3455, zip=14512
$tests['T4_en_append_zip_14512'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd, Al Aziziyah, RMAD3455',
    'address2' => '7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
];

// T5: Full National Address format in address field, zip=14512-7664
$tests['T5_full_national_format'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => 'RMAD3455, 3455 Ahmad Bin Rushd, Al Aziziyah District, 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
];

// T6: Short address FIRST in address field
$tests['T6_rmad_first_in_addr'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => 'RMAD3455 3455 أحمد بن رشد حي العزيزية',
    'address2' => '7664',
    'city' => 'الرياض', 'province' => 'الرياض',
    'zip' => '14512', 'country' => 'SA',
];

// T7: ZIP=14512-RMAD3455 (combined format)
$tests['T7_zip_combined_format'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah',
    'address2' => 'RMAD3455 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512RMAD3455', 'country' => 'SA',
];

// T8: Only RMAD in address2, zip=14512, address=full SPL
$tests['T8_rmad_in_addr2_only'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah District',
    'address2' => 'Short Address: RMAD3455, Secondary: 7664',
    'city' => 'Riyadh', 'province' => 'Riyadh',
    'zip' => '14512', 'country' => 'SA',
];

// T9: province=Ar Riyad (some AliExpress internal naming), zip=RMAD3455
$tests['T9_province_ar_riyad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd, Al Aziziyah, Riyadh, RMAD3455',
    'city' => 'Riyadh',
    'province' => 'Ar Riyad',
    'zip' => 'RMAD3455', 'country' => 'SA',
];

// T10: Try with the EXACT format AliExpress uses when displaying SA addresses
$tests['T10_ae_display_format'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
    'address' => 'حي العزيزية, الرياض, المملكة العربية السعودية',
    'address2' => '3455',
    'city' => 'الرياض', 'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455', 'country' => 'SA',
    'passport_no' => 'RMAD3455',
    'national_address' => 'RMAD3455',
];

$idx = 0;
foreach ($tests as $name => $addr) {
    $idx++;
    $correlation = "SPL5-{$name}-" . time();
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
    
    echo "=== [{$idx}] {$name} ===\n";
    $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
    $body = $res['body'] ?? [];
    $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $err = $body['error_response'] ?? [];
    
    $success = $result['is_success'] ?? false;
    $errCode = $result['error_code'] ?? ($err['code'] ?? null);
    $errMsg = $result['error_msg'] ?? ($err['msg'] ?? null);
    $orders = $result['order_list'] ?? null;
    
    if ($success) {
        echo "✅✅✅ SUCCESS! Order: " . json_encode($orders) . "\n";
        echo "🏆 WINNING FORMAT: " . json_encode($addr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        break;
    } else {
        echo "❌ {$errCode}: {$errMsg}\n";
    }
    usleep(500000);
}

echo "\n=== DONE ===\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_sa_append_v5.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_sa_append_v5.php && rm probe_sa_append_v5.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
