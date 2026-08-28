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

// ============================================
// SPL EXACT DATA:
// العنوان المختصر: RMAD3455
// رقم المبنى: 3455
// الشارع: أحمد بن رشد
// الرقم الفرعي: 7664
// الحي: حي العزيزية
// الرمز البريدي: 14512
// المدينة: الرياض
// ============================================

$tests = [];

// P1: SPL-format address in Arabic with zip=RMAD3455, building in address2
$tests['P1_spl_arabic_zip_rmad'] = [
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

// P2: SPL-format in English with zip=RMAD3455
$tests['P2_spl_english_zip_rmad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah',
    'address2' => '7664',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

// P3: Standard 5-digit zip with national address in address2 as "RMAD3455"
$tests['P3_zip14512_addr2_rmad'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah',
    'address2' => 'RMAD3455',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '14512',
    'country' => 'SA',
];

// P4: Full SPL national address format with zip=14512-7664 (zip+secondary)
$tests['P4_zip_combined'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah District',
    'address2' => 'RMAD3455',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '14512-7664',
    'country' => 'SA',
];

// P5: passport_no + tax_number + zip=RMAD3455 (SPL exact street Arabic)
$tests['P5_passport_tax_spl'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد',
    'address2' => 'حي العزيزية 7664',
    'city' => 'الرياض',
    'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455',
    'country' => 'SA',
    'passport_no' => 'RMAD3455',
    'tax_number' => 'RMAD3455',
];

// P6: Try with phone having leading 0
$tests['P6_phone_leading_zero'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '0572124578',
    'mobile_no' => '0572124578',
    'address' => '3455 Ahmad Bin Rushd St',
    'address2' => 'Al Aziziyah District 7664',
    'city' => 'Riyadh',
    'province' => 'Riyadh Region',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

// P7: All fields in English, province="Ar Riyad" (AliExpress internal naming)
$tests['P7_en_ar_riyad_province'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmed Bin Rashid Street',
    'address2' => 'Al Aziziyah 7664',
    'city' => 'Riyadh',
    'province' => 'Ar Riyad',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

// P8: Try location_tree_address_id approach (common AliExpress internal IDs for Riyadh)
$tests['P8_location_tree_id'] = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd',
    'address2' => 'Al Aziziyah 7664',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => 'RMAD3455',
    'country' => 'SA',
    'location_tree_address_id' => 'RMAD3455',
];

$idx = 0;
foreach ($tests as $name => $addr) {
    $idx++;
    $correlation = "SPLPROBE-{$name}-" . time();
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
        echo "✅ SUCCESS! Order: " . json_encode($orders) . "\n";
        echo "WINNING ADDRESS FORMAT: " . json_encode($addr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        break; // Stop on first success!
    } else {
        echo "❌ FAILED: {$errCode} — {$errMsg}\n";
    }
    echo "\n";
    
    // Small delay between attempts
    usleep(500000);
}

echo "\n=== ALL PROBES COMPLETE ===\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_sa_spl_v4.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_sa_spl_v4.php && rm probe_sa_spl_v4.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
