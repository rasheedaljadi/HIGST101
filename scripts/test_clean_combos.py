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

$combos = [
    // 1. Pure English, zip=RMAD3455, no address2
    [
        'name' => '1. Pure English, zip=RMAD3455, no address2, passport_no=RMAD3455',
        'addr' => [
            'contact_person' => 'Mostafa Mo Bamashmous',
            'full_name' => 'Mostafa Mo Bamashmous',
            'phone_country' => '966',
            'mobile_no' => '572124578',
            'country' => 'SA',
            'province' => 'Riyadh',
            'city' => 'Riyadh',
            'address' => 'Al Aziziyah 3455',
            'zip' => 'RMAD3455',
            'passport_no' => 'RMAD3455',
        ],
    ],
    // 2. Pure English, zip=14512, address=RMAD3455
    [
        'name' => '2. Pure English, address=RMAD3455, zip=14512',
        'addr' => [
            'contact_person' => 'Mostafa Mo Bamashmous',
            'full_name' => 'Mostafa Mo Bamashmous',
            'phone_country' => '966',
            'mobile_no' => '572124578',
            'country' => 'SA',
            'province' => 'Riyadh',
            'city' => 'Riyadh',
            'address' => 'RMAD3455',
            'address2' => 'Al Aziziyah 3455',
            'zip' => '14512',
        ],
    ],
    // 3. Pure English, address=Al Aziziyah, address2=RMAD3455, no numbers in address2
    [
        'name' => '3. address=Al Aziziyah, address2=RMAD3455',
        'addr' => [
            'contact_person' => 'Mostafa Mo Bamashmous',
            'full_name' => 'Mostafa Mo Bamashmous',
            'phone_country' => '966',
            'mobile_no' => '572124578',
            'country' => 'SA',
            'province' => 'Riyadh',
            'city' => 'Riyadh',
            'address' => 'Al Aziziyah',
            'address2' => 'RMAD3455',
            'zip' => '14512',
        ],
    ],
    // 4. Pure English, zip=RMAD3455, no passport_no, no address2
    [
        'name' => '4. zip=RMAD3455 only',
        'addr' => [
            'contact_person' => 'Mostafa Mo Bamashmous',
            'full_name' => 'Mostafa Mo Bamashmous',
            'phone_country' => '966',
            'mobile_no' => '572124578',
            'country' => 'SA',
            'province' => 'Riyadh',
            'city' => 'Riyadh',
            'address' => 'Al Aziziyah',
            'zip' => 'RMAD3455',
        ],
    ],
    // 5. phone_country="+966", mobile_no="572124578", passport_no="RMAD3455", tax_number="RMAD3455", zip="RMAD3455"
    [
        'name' => '5. All SA identifiers as RMAD3455',
        'addr' => [
            'contact_person' => 'Mostafa Mo Bamashmous',
            'full_name' => 'Mostafa Mo Bamashmous',
            'phone_country' => '+966',
            'mobile_no' => '572124578',
            'country' => 'SA',
            'province' => 'Riyadh',
            'city' => 'Riyadh',
            'address' => 'Al Aziziyah',
            'address2' => 'RMAD3455',
            'zip' => 'RMAD3455',
            'passport_no' => 'RMAD3455',
            'tax_number' => 'RMAD3455',
        ],
    ],
];

foreach ($combos as $c) {
    echo "========================================\n";
    echo "Testing: {$c['name']}\n";
    
    $params = [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_' . time() . '_' . rand(100, 999),
            'logistics_address' => $c['addr'],
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
        echo "\n🎉🎉🎉 SUCCESS! ORDER CREATED ON ALIEXPRESS! 🎉🎉🎉\n";
        echo "Order List:\n";
        print_r($result['order_list']);
        break;
    } else {
        echo "Failed: " . ($result['error_code'] ?? $res['code'] ?? 'ERR') . " -> " . ($result['error_msg'] ?? $res['message'] ?? 'Unknown') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_clean_combos.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_clean_combos.php && rm test_clean_combos.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
