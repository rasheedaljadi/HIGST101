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
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';
$code = 'RMAD3455';

// Test matrix of different parameter combinations
$matrix = [
    // 1. Phone formats
    'phone_05_prefix' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '966', 'phone_num' => '0572124578', 'mobile_no' => '0572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Riyadh', 'province' => 'Riyadh',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    'phone_no_0_prefix' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '966', 'phone_num' => '572124578', 'mobile_no' => '572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Riyadh', 'province' => 'Riyadh',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    'phone_full_966' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '+966', 'phone_num' => '+966572124578', 'mobile_no' => '+966572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Riyadh', 'province' => 'Riyadh',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    // 2. City / Province variations
    'city_Ar_Riyad' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '966', 'mobile_no' => '0572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Ar Riyad', 'province' => 'Ar Riyad',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    'city_Riyadh_Province_ArRiyadh' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '966', 'mobile_no' => '0572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Riyadh', 'province' => 'Ar Riyadh',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    'city_Riyadh_Province_Riyadh_Region' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '966', 'mobile_no' => '0572124578',
        'address' => '3455 Ahmad Ibn Rushd, Al Aziziyah',
        'city' => 'Riyadh', 'province' => 'Riyadh Region',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    // 3. Arabic variations
    'arabic_spl_standard' => [
        'contact_person' => 'مصطفى بامشموس',
        'full_name' => 'مصطفى بامشموس',
        'phone_country' => '966', 'mobile_no' => '0572124578',
        'address' => '3455 شارع احمد بن رشد حي العزيزية',
        'city' => 'الرياض', 'province' => 'الرياض',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
    'arabic_spl_full_details' => [
        'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
        'phone_country' => '966', 'mobile_no' => '0572124578',
        'address' => 'حي العزيزية, شارع أحمد بن رشد, مبنى 3455',
        'address2' => 'الرقم الإضافي 7664',
        'city' => 'الرياض', 'province' => 'منطقة الرياض',
        'zip' => 'RMAD3455', 'country' => 'SA',
    ],
];

foreach ($matrix as $name => $addr) {
    echo "=== PROBING: {$name} ===\n";
    $correlation = 'MATRIX-' . time() . '-' . substr(md5($name), 0, 4);
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
        echo "🎉🎉🎉 SUCCESS! Orders: " . json_encode($orders) . "\n";
        echo "Winning Address Config:\n";
        print_r($addr);
        break;
    } else {
        echo "❌ {$errCode}: {$errMsg}\n\n";
    }
    usleep(400000);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_matrix_sa.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_matrix_sa.php && rm probe_matrix_sa.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
