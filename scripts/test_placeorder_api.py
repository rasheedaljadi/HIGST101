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
    'aliexpress.trade.buy.placeorder' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PO_' . time(),
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
            'logistics_address' => [
                'contact_person' => 'Mostafa Mo Bamashmous',
                'full_name' => 'Mostafa Mo Bamashmous',
                'phone_country' => '966',
                'mobile_no' => '572124578',
                'country' => 'SA',
                'province' => 'Riyadh',
                'city' => 'Riyadh',
                'address' => 'حي العزيزية 3455',
                'zip' => '14512',
                'passport_no' => 'RMAD3455',
            ],
        ],
    ],
    'aliexpress.ds.order.create without logistics_address (default address in AE account)' => [
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'PO_DEF_' . time(),
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

foreach ($tests as $m => $params) {
    echo "========================================\n";
    echo "Testing {$m}...\n";
    $method = str_contains($m, 'trade.buy') ? 'aliexpress.trade.buy.placeorder' : 'aliexpress.ds.order.create';
    $res = $apiClient->call($method, $latestToken->access_token, $params);
    print_r($res['body']);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_placeorder_api.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_placeorder_api.php && rm test_placeorder_api.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
