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

// Let's test combinations of address parameters
$tests = [
    'Test 1: zip=14512, passport_no=RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'phone_country' => '966',
        'address' => 'حي العزيزية, 3455',
        'address2' => '3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14512',
        'country' => 'SA',
        'passport_no' => 'RMAD3455',
    ],
    'Test 2: zip=14512, national_address=RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'phone_country' => '966',
        'address' => 'حي العزيزية, 3455',
        'address2' => '3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14512',
        'country' => 'SA',
        'national_address' => 'RMAD3455',
    ],
    'Test 3: zip=14512, foreigner_passport_no=RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'phone_country' => '966',
        'address' => 'حي العزيزية, 3455',
        'address2' => '3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14512',
        'country' => 'SA',
        'foreigner_passport_no' => 'RMAD3455',
    ],
    'Test 4: zip=14512, tax_number=RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'phone_country' => '966',
        'address' => 'حي العزيزية, 3455',
        'address2' => '3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14512',
        'country' => 'SA',
        'tax_number' => 'RMAD3455',
    ],
    'Test 5: zip=14512, all extra SA fields = RMAD3455' => [
        'contact_person' => 'Mostafa Mo Bamashmous',
        'full_name' => 'Mostafa Mo Bamashmous',
        'phone_num' => '572124578',
        'mobile_no' => '572124578',
        'phone_country' => '966',
        'address' => 'حي العزيزية, 3455',
        'address2' => '3455',
        'city' => 'Riyadh',
        'province' => 'Riyadh',
        'zip' => '14512',
        'country' => 'SA',
        'passport_no' => 'RMAD3455',
        'tax_number' => 'RMAD3455',
        'foreigner_passport_no' => 'RMAD3455',
        'national_address' => 'RMAD3455',
        'national_number' => 'RMAD3455',
        'short_address' => 'RMAD3455',
    ],
];

foreach ($tests as $name => $addr) {
    echo "========================================\n";
    echo "Running {$name}...\n";
    
    $params = [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_' . time() . '_' . rand(100, 999),
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
    
    echo "OK: " . ($res['ok'] ? 'YES' : 'NO') . "\n";
    echo "Is Success: " . ($result['is_success'] ? 'YES' : 'NO') . "\n";
    if (! empty($result['order_list'])) {
        echo "ORDER CREATED! Order List:\n";
        print_r($result['order_list']);
        break;
    } else {
        echo "Error: " . ($result['error_code'] ?? 'NONE') . " - " . ($result['error_msg'] ?? $res['message'] ?? 'NONE') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_address_combinations.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_address_combinations.php && rm test_address_combinations.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
