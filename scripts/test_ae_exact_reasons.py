import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\Gateways\AliExpressOrderSubmissionGateway;
use Illuminate\Support\Facades\DB;

$apiClient = app(AliExpressApiClient::class);
$authResolver = app(AliExpressAuthorizationContextResolver::class);
$auth = $authResolver->resolveForDropshipperSubmission(null);
$gateway = app(AliExpressOrderSubmissionGateway::class);
$shippingAddress = $gateway->resolveWarehouseShippingAddress();

echo "=== Test 1: Calling order.create for Target SKU 12000059778048925 WITH sku_attr='14:173#16GB 512GB Green' ===\n";
$params1 = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'DIAG-TEST-001-' . time(),
        'logistics_address' => $shippingAddress,
        'product_items' => [
            [
                'product_count' => 1,
                'product_id' => 1005011942764190,
                'sku_attr' => '14:173#16GB 512GB Green',
                'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
            ]
        ],
    ],
    'ds_extend_request' => [
        'trade_extra_param' => [
            'business_model' => 'retail',
            'nat_addr' => 'RMAD3455',
        ],
        'payment' => [
            'pay_currency' => 'USD',
            'try_to_pay' => 'false',
        ],
    ],
];

$res1 = $apiClient->call('aliexpress.ds.order.create', $auth->accessToken, $params1);
echo "Response for SKU 12000059778048925 (Stock=0, with sku_attr):\n";
echo json_encode($res1['body'] ?? $res1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== Test 2: Calling order.create for In-Stock SKU 12000059778048923 WITH sku_attr='14:10#16GB 512GB Black' ===\n";
$params2 = [
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'DIAG-TEST-002-' . time(),
        'logistics_address' => $shippingAddress,
        'product_items' => [
            [
                'product_count' => 1,
                'product_id' => 1005011942764190,
                'sku_attr' => '14:10#16GB 512GB Black',
                'logistics_service_name' => 'CAINIAO_FULFILLMENT_STD',
            ]
        ],
    ],
    'ds_extend_request' => [
        'trade_extra_param' => [
            'business_model' => 'retail',
            'nat_addr' => 'RMAD3455',
        ],
        'payment' => [
            'pay_currency' => 'USD',
            'try_to_pay' => 'false',
        ],
    ],
];

$res2 = $apiClient->call('aliexpress.ds.order.create', $auth->accessToken, $params2);
echo "Response for SKU 12000059778048923 (Stock=3, with sku_attr):\n";
echo json_encode($res2['body'] ?? $res2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== Test 3: Why was sku_attr empty in preflight? ===\n";
$localImport = DB::table('aliexpress_product_imports')
    ->where('aliexpress_product_id', '1005011942764190')
    ->first();
if ($localImport) {
    echo "Found localImport in aliexpress_product_imports! ID: {$localImport->id}\n";
    $snap = json_decode((string)$localImport->payload_snapshot, true);
    echo "Variants count in payload_snapshot: " . count($snap['variants'] ?? []) . "\n";
    print_r($snap['variants'] ?? []);
} else {
    echo "No row in aliexpress_product_imports for 1005011942764190\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_ae_exact_reasons.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_ae_exact_reasons.php && rm test_ae_exact_reasons.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
