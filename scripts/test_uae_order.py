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
    'Test UAE Address' => [
        'contact_person' => 'Mostafa Bamashmous',
        'full_name' => 'Mostafa Bamashmous',
        'phone_country' => '971',
        'mobile_no' => '501234567',
        'phone_num' => '501234567',
        'country' => 'AE',
        'province' => 'Dubai',
        'city' => 'Dubai',
        'address' => 'Sheikh Zayed Road, Trade Centre 1',
        'zip' => '00000',
    ],
];

foreach ($tests as $name => $addr) {
    echo "========================================\n";
    echo "Running {$name}...\n";
    
    $params = [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PO_TEST_AE_' . time(),
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
    echo "Result:\n";
    print_r($result);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_uae_order.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_uae_order.php && rm test_uae_order.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
