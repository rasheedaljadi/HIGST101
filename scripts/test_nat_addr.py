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

echo "=== DISCOVERED FIELD: nat_addr (SA natAddr) ===\n";
echo "Token ID: {$token->id}, Account: {$token->account}\n";

$productId = '1005010737996063';
$skuId = '12000053357140815';
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';
$natAddr = 'RMAD3455';

// Base logistics address (clean 5-digit zip + official SPL details)
$baseLogistics = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '0572124578',
    'mobile_no' => '0572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية',
    'address2' => '7664',
    'city' => 'الرياض',
    'province' => 'منطقة الرياض',
    'zip' => '14512',
    'country' => 'SA',
];

$productItems = [[
    'product_count' => 1,
    'product_id' => $productId,
    'sku_id' => $skuId,
    'sku_attr' => $skuAttr,
    'sku_define_type' => 'sku_id',
    'logistics_service_name' => $shippingService,
]];

$attempts = [];

// Attempt 1: nat_addr as Top-Level API parameter (alongside param_place_order_request4_open_api_d_t_o)
$attempts['1_top_level_api_param'] = [
    'nat_addr' => $natAddr,
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'NATADDR-1-' . time(),
        'logistics_address' => $baseLogistics,
        'product_items' => $productItems,
    ],
];

// Attempt 2: nat_addr inside param_place_order_request4_open_api_d_t_o AND top-level
$attempts['2_inside_dto_and_top'] = [
    'nat_addr' => $natAddr,
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'NATADDR-2-' . time(),
        'nat_addr' => $natAddr,
        'logistics_address' => $baseLogistics,
        'product_items' => $productItems,
    ],
];

// Attempt 3: nat_addr inside logistics_address AND top-level AND zip=14512
$attempts['3_inside_logistics_and_top'] = [
    'nat_addr' => $natAddr,
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'NATADDR-3-' . time(),
        'logistics_address' => array_merge($baseLogistics, ['nat_addr' => $natAddr]),
        'product_items' => $productItems,
    ],
];

// Attempt 4: nat_addr inside logistics_address AND zip=RMAD3455
$attempts['4_zip_rmad_and_nat_addr'] = [
    'nat_addr' => $natAddr,
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'NATADDR-4-' . time(),
        'logistics_address' => array_merge($baseLogistics, ['zip' => $natAddr, 'nat_addr' => $natAddr]),
        'product_items' => $productItems,
    ],
];

// Attempt 5: English address with nat_addr
$englishLogistics = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 Ahmad Bin Rushd St, Al Aziziyah Dist',
    'address2' => '7664',
    'city' => 'Riyadh',
    'province' => 'Riyadh',
    'zip' => '14512',
    'country' => 'SA',
    'nat_addr' => $natAddr,
];

$attempts['5_english_logistics_with_nat_addr'] = [
    'nat_addr' => $natAddr,
    'param_place_order_request4_open_api_d_t_o' => [
        'out_order_id' => 'NATADDR-5-' . time(),
        'nat_addr' => $natAddr,
        'logistics_address' => $englishLogistics,
        'product_items' => $productItems,
    ],
];

foreach ($attempts as $name => $params) {
    echo "========================================\n";
    echo "SUBMITTING ATTEMPT: {$name}\n";
    echo "========================================\n";
    
    $res = $apiClient->call('aliexpress.ds.order.create', $accessToken, $params);
    $body = $res['body'] ?? [];
    $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
    $result = $resp['result'] ?? [];
    $err = $body['error_response'] ?? [];
    
    $isSuccess = $result['is_success'] ?? false;
    $errCode = $result['error_code'] ?? ($err['code'] ?? null);
    $errMsg = $result['error_msg'] ?? ($err['msg'] ?? null);
    $orders = $result['order_list'] ?? null;
    
    echo "HTTP Ok: " . ($res['ok'] ? 'true' : 'false') . "\n";
    echo "is_success: " . ($isSuccess ? 'TRUE' : 'FALSE') . "\n";
    
    if ($isSuccess) {
        echo "\n🎉🎉🎉 SUCCESS! OFFICIAL ALIEXPRESS ORDER CREATED! 🎉🎉🎉\n";
        echo "Order Numbers: " . json_encode($orders) . "\n";
        echo "Winning Attempt: {$name}\n";
        echo "Full Result:\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        break;
    } else {
        echo "Error: [{$errCode}] {$errMsg}\n";
        if (!empty($body)) {
            echo "Raw Body: " . json_encode($body, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
    usleep(500000);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_nat_addr.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_nat_addr.php && rm test_nat_addr.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
