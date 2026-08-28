import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Models\AliExpressToken;

$latestToken = AliExpressToken::latest()->first();
$apiClient = app(AliExpressApiClient::class);

$productId = '1005010737996063';
$skuId = '12000053357140815';

$tests = [
    'SA Test 1: Arabic Province and City with RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'country' => 'SA',
        'province' => 'منطقة الرياض',
        'city' => 'الرياض',
        'address' => 'حي العزيزية 3455 RMAD3455',
        'address2' => 'RMAD3455',
        'zip' => '14512',
        'passport_no' => 'RMAD3455',
        'tax_number' => 'RMAD3455',
    ],
    'SA Test 2: Ar Riyad province/city' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'country' => 'SA',
        'province' => 'Ar Riyad',
        'city' => 'Ar Riyad',
        'address' => 'Al Aziziyah 3455 RMAD3455',
        'address2' => 'RMAD3455',
        'zip' => '14512',
        'passport_no' => 'RMAD3455',
    ],
    'SA Test 3: zip=14512, address="RMAD3455"' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'country' => 'SA',
        'province' => 'Riyadh Region',
        'city' => 'Riyadh',
        'address' => 'RMAD3455',
        'address2' => 'Al Aziziyah 3455',
        'zip' => '14512',
        'passport_no' => 'RMAD3455',
    ],
    'SA Test 4: address contains National Address: RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_country' => '966',
        'mobile_no' => '572124578',
        'country' => 'SA',
        'province' => 'Riyadh Province',
        'city' => 'Riyadh',
        'address' => 'Al Aziziyah, Building 3455',
        'address2' => 'National Address: RMAD3455',
        'zip' => '14512',
        'passport_no' => 'RMAD3455',
    ],
];

foreach ($tests as $name => $addr) {
    echo "========================================\n";
    echo "Running: {$name}\n";
    
    $params = [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PO_SA_' . time() . '_' . rand(100, 999),
            'logistics_address' => $addr,
            'product_items' => [
                [
                    'product_count' => 1,
                    'product_id' => $productId,
                    'sku_id' => $skuId,
                    'sku_attr' => '14:201447015#NO PAD',
                    'sku_define_type' => 'sku_id',
                    'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
                ]
            ],
        ],
    ];
    
    $res = $apiClient->call('aliexpress.ds.order.create', $latestToken->access_token, $params);
    $body = $res['body'] ?? [];
    $result = $body['aliexpress_ds_order_create_response']['result'] ?? [];
    
    if (! empty($result['is_success']) && $result['is_success'] === true) {
        echo "🎉🎉🎉 SUCCESS FOR SA! ORDER CREATED: " . json_encode($result['order_list']) . "\n";
        break;
    } else {
        echo "Failed: " . ($result['error_code'] ?? 'ERR') . " -> " . ($result['error_msg'] ?? $res['message'] ?? 'Unknown') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_sa_variations.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_sa_variations.php && rm test_sa_variations.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
