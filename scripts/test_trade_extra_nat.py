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

echo "=== TESTING TRADE_EXTRA_PARAM.NAT_ADDR SCHEMA ===\n";
echo "Token ID: {$token->id}, Account: {$token->account}\n";

$productId = '1005010737996063';
$skuId = '12000053357140815';
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';
$natAddr = 'RMAD3455';

// Variations of the exact trade_extra_param.nat_addr structure
$tests = [
    // V1: Exact specification from the report (9-digit phone starting with 5, +966 country code, 5-digit zip)
    'V1_exact_report_spec' => [
        'ds_extend_request' => [
            'trade_extra_param' => [
                'business_model' => 'retail',
                'nat_addr' => $natAddr,
            ],
            'payment' => [
                'pay_currency' => 'USD',
                'try_to_pay' => 'false',
            ],
        ],
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TRADE-SPEC-1-' . time(),
            'logistics_address' => [
                'address' => '3455 Ahmad Bin Rushd St',
                'address2' => 'Al Aziziyah, 7664',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'country' => 'SA',
                'full_name' => 'Mostafa Bamashmous',
                'contact_person' => 'Mostafa Bamashmous',
                'mobile_no' => '572124578',
                'phone_country' => '+966',
                'zip' => '14512',
                'locale' => 'en_US',
            ],
            'product_items' => [[
                'product_id' => (int) $productId,
                'product_count' => 1,
                'sku_attr' => $skuAttr,
                'logistics_service_name' => $shippingService,
                'order_memo' => 'Hayest internal procurement',
            ]],
        ],
    ],

    // V2: Phone country as '966' without plus + phone starting with 05
    'V2_phone_05_prefix' => [
        'ds_extend_request' => [
            'trade_extra_param' => [
                'business_model' => 'retail',
                'nat_addr' => $natAddr,
            ],
            'payment' => [
                'pay_currency' => 'USD',
                'try_to_pay' => 'false',
            ],
        ],
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TRADE-SPEC-2-' . time(),
            'logistics_address' => [
                'address' => '3455 Ahmad Bin Rushd St',
                'address2' => 'Al Aziziyah, 7664',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'country' => 'SA',
                'full_name' => 'Mostafa Bamashmous',
                'contact_person' => 'Mostafa Bamashmous',
                'mobile_no' => '0572124578',
                'phone_country' => '966',
                'zip' => '14512',
                'locale' => 'en_US',
            ],
            'product_items' => [[
                'product_id' => (int) $productId,
                'product_count' => 1,
                'sku_attr' => $skuAttr,
                'logistics_service_name' => $shippingService,
            ]],
        ],
    ],

    // V3: Extended zip format 14512-7664 (as noted by DSers in report)
    'V3_extended_zip_format' => [
        'ds_extend_request' => [
            'trade_extra_param' => [
                'business_model' => 'retail',
                'nat_addr' => $natAddr,
            ],
            'payment' => [
                'pay_currency' => 'USD',
                'try_to_pay' => 'false',
            ],
        ],
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TRADE-SPEC-3-' . time(),
            'logistics_address' => [
                'address' => '3455 Ahmad Bin Rushd St',
                'address2' => 'Al Aziziyah',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'country' => 'SA',
                'full_name' => 'Mostafa Bamashmous',
                'contact_person' => 'Mostafa Bamashmous',
                'mobile_no' => '572124578',
                'phone_country' => '+966',
                'zip' => '14512-7664',
                'locale' => 'en_US',
            ],
            'product_items' => [[
                'product_id' => (int) $productId,
                'product_count' => 1,
                'sku_attr' => $skuAttr,
                'logistics_service_name' => $shippingService,
            ]],
        ],
    ],

    // V4: trade_extra_param JSON string inside ds_extend_request
    'V4_stringified_trade_extra' => [
        'ds_extend_request' => json_encode([
            'trade_extra_param' => json_encode([
                'business_model' => 'retail',
                'nat_addr' => $natAddr,
            ]),
            'payment' => [
                'pay_currency' => 'USD',
                'try_to_pay' => 'false',
            ],
        ]),
        'param_place_order_request4_open_api_d_t_o' => [
            'out_order_id' => 'TRADE-SPEC-4-' . time(),
            'logistics_address' => [
                'address' => '3455 Ahmad Bin Rushd St',
                'address2' => 'Al Aziziyah, 7664',
                'city' => 'Riyadh',
                'province' => 'Riyadh',
                'country' => 'SA',
                'full_name' => 'Mostafa Bamashmous',
                'contact_person' => 'Mostafa Bamashmous',
                'mobile_no' => '572124578',
                'phone_country' => '+966',
                'zip' => '14512',
                'locale' => 'en_US',
            ],
            'product_items' => [[
                'product_id' => (int) $productId,
                'product_count' => 1,
                'sku_attr' => $skuAttr,
                'logistics_service_name' => $shippingService,
            ]],
        ],
    ],
];

foreach ($tests as $name => $params) {
    echo "========================================\n";
    echo "EXECUTING: {$name}\n";
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
    
    echo "HTTP Status: " . $res['status'] . ", Ok: " . ($res['ok'] ? 'true' : 'false') . "\n";
    echo "is_success: " . ($isSuccess ? 'TRUE' : 'FALSE') . "\n";
    
    if ($isSuccess) {
        echo "\n🎉🎉🎉 SUCCESS! OFFICIAL ALIEXPRESS ORDER CREATED! 🎉🎉🎉\n";
        echo "Order Numbers: " . json_encode($orders) . "\n";
        echo "Winning Schema: {$name}\n";
        echo "Full Result:\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        break;
    } else {
        echo "Error Code: [{$errCode}]\n";
        echo "Error Msg: {$errMsg}\n";
        if (!empty($body)) {
            echo "Raw Body: " . json_encode($body, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
    usleep(500000);
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_trade_extra_nat.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_trade_extra_nat.php && rm test_trade_extra_nat.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
