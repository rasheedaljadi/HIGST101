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
    // 1. extend_map inside param_place_order_request4_open_api_d_t_o
    'extend_map with national_address' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_EXT_' . time(),
            'extend_map' => json_encode(['national_address' => 'RMAD3455', 'short_address' => 'RMAD3455', 'passport_no' => 'RMAD3455']),
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'Riyadh',
                'city' => 'Riyadh',
                'address' => 'حي العزيزية 3455',
                'address2' => '3455',
                'zip' => '14512',
            ],
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
    ],
    // 2. top level fields on param_place_order_request4_open_api_d_t_o
    'top level national_address / passport_no' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_TOP_' . time(),
            'national_address' => 'RMAD3455',
            'passport_no' => 'RMAD3455',
            'tax_number' => 'RMAD3455',
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'Riyadh',
                'city' => 'Riyadh',
                'address' => 'حي العزيزية 3455',
                'address2' => '3455',
                'zip' => '14512',
            ],
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
    ],
    // 3. address starting with RMAD3455
    'address starting with RMAD3455' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_ADDR_' . time(),
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'Riyadh',
                'city' => 'Riyadh',
                'address' => 'RMAD3455 Al Aziziyah 3455',
                'address2' => 'RMAD3455',
                'zip' => 'RMAD3455',
            ],
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
    ],
    // 4. address format: "National Address: RMAD3455"
    'address containing National Address: RMAD3455' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_LBL_' . time(),
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'Riyadh',
                'city' => 'Riyadh',
                'address' => 'Al Aziziyah 3455 National Address: RMAD3455',
                'address2' => 'National Address: RMAD3455',
                'zip' => '14512',
            ],
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
    ],
    // 5. exact address: address="حي العزيزية", address2="RMAD3455", zip="14512"
    'address2 is strictly RMAD3455' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TEST_EXACT_' . time(),
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'منطقة الرياض',
                'city' => 'الرياض',
                'address' => 'حي العزيزية, الرياض, المملكة العربية السعودية',
                'address2' => 'RMAD3455',
                'zip' => '14512',
            ],
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
    ],
];

foreach ($tests as $name => $params) {
    echo "========================================\n";
    echo "Running: {$name}\n";
    
    $res = $apiClient->call('aliexpress.ds.order.create', $latestToken->access_token, $params);
    $body = $res['body'] ?? [];
    $result = $body['aliexpress_ds_order_create_response']['result'] ?? [];
    
    if (! empty($result['is_success']) && $result['is_success'] === true) {
        echo ">>> SUCCESS! ORDER CREATED! Order ID:\n";
        print_r($result['order_list']);
        break;
    } else {
        echo "Failed: " . ($result['error_code'] ?? 'ERR') . " -> " . ($result['error_msg'] ?? $res['message'] ?? 'Unknown') . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_advanced_formats.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_advanced_formats.php && rm test_advanced_formats.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
