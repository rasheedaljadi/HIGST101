import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\Http;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();
$accessToken = $token->access_token;

$appKey = config('aliexpress.app_key');
$appSecret = config('aliexpress.app_secret');
$endpoint = config('aliexpress.business_url');

echo "=== API Config ===\n";
echo "Endpoint: {$endpoint}\n";
echo "App Key: {$appKey}\n\n";

// 1. First: Check what the FULL API response contains (not just result/error_code)
// Build raw request manually to see EVERYTHING

$productId = '1005010737996063';
$skuId = '12000053357140815';
$skuAttr = '14:201447015#NO PAD';
$shippingService = 'CAINIAO_FULFILLMENT_STD';

$logisticsAddress = [
    'contact_person' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'full_name' => 'Mostafa Mohammed Ahmed Bamashmoos',
    'phone_country' => '966',
    'phone_num' => '572124578',
    'mobile_no' => '572124578',
    'address' => '3455 أحمد بن رشد, حي العزيزية, RMAD3455',
    'address2' => '7664',
    'city' => 'الرياض',
    'province' => 'منطقة الرياض',
    'zip' => 'RMAD3455',
    'country' => 'SA',
];

$dto = [
    'out_order_id' => 'RAW-DEBUG-' . time(),
    'logistics_address' => $logisticsAddress,
    'product_items' => [[
        'product_count' => 1,
        'product_id' => $productId,
        'sku_id' => $skuId,
        'sku_attr' => $skuAttr,
        'sku_define_type' => 'sku_id',
        'logistics_service_name' => $shippingService,
    ]],
];

echo "=== RAW DTO being sent (before JSON encoding) ===\n";
echo json_encode($dto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Build the exact same request our API client builds
$request = [
    'param_place_order_request4_open_api_d_t_o' => json_encode($dto, JSON_UNESCAPED_UNICODE),
    'app_key' => $appKey,
    'access_token' => $accessToken,
    'method' => 'aliexpress.ds.order.create',
    'format' => 'json',
    'sign_method' => 'sha256',
    'timestamp' => (string)(int)(microtime(true) * 1000),
];

echo "=== param_place_order_request4_open_api_d_t_o (as sent to AE) ===\n";
echo $request['param_place_order_request4_open_api_d_t_o'] . "\n\n";

// Sign it
ksort($request);
$base = '';
foreach ($request as $k => $v) { $base .= $k . $v; }
$request['sign'] = strtoupper(hash_hmac('sha256', $base, $appSecret));

echo "=== Sending raw HTTP POST to {$endpoint} ===\n";
$response = Http::asForm()
    ->connectTimeout(30)
    ->timeout(60)
    ->post($endpoint, $request);

echo "HTTP Status: " . $response->status() . "\n";
echo "=== FULL RAW RESPONSE BODY ===\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Now try with logistics_address as a JSON STRING inside the DTO string
// (double-encoding test)
echo "\n=== TEST 2: Double JSON encoding logistics_address ===\n";
$dto2 = [
    'out_order_id' => 'RAW-DOUBLE-' . time(),
    'logistics_address' => json_encode($logisticsAddress, JSON_UNESCAPED_UNICODE),
    'product_items' => json_encode([[
        'product_count' => 1,
        'product_id' => $productId,
        'sku_id' => $skuId,
        'sku_attr' => $skuAttr,
        'sku_define_type' => 'sku_id',
        'logistics_service_name' => $shippingService,
    ]], JSON_UNESCAPED_UNICODE),
];

$request2 = [
    'param_place_order_request4_open_api_d_t_o' => json_encode($dto2, JSON_UNESCAPED_UNICODE),
    'app_key' => $appKey,
    'access_token' => $accessToken,
    'method' => 'aliexpress.ds.order.create',
    'format' => 'json',
    'sign_method' => 'sha256',
    'timestamp' => (string)(int)(microtime(true) * 1000),
];

ksort($request2);
$base2 = '';
foreach ($request2 as $k => $v) { $base2 .= $k . $v; }
$request2['sign'] = strtoupper(hash_hmac('sha256', $base2, $appSecret));

$response2 = Http::asForm()->connectTimeout(30)->timeout(60)->post($endpoint, $request2);
echo "HTTP Status: " . $response2->status() . "\n";
$body2 = $response2->json() ?? [];
$resp2 = $body2['aliexpress_ds_order_create_response'] ?? $body2;
$result2 = $resp2['result'] ?? [];
$err2 = $body2['error_response'] ?? [];
$success2 = $result2['is_success'] ?? false;
echo "Success: " . ($success2 ? 'YES' : 'NO') . "\n";
echo "Error: " . ($result2['error_code'] ?? $err2['code'] ?? 'none') . " — " . ($result2['error_msg'] ?? $err2['msg'] ?? '') . "\n";

if ($success2) {
    echo "✅✅✅ DOUBLE ENCODING WORKED! Orders: " . json_encode($result2['order_list'] ?? null) . "\n";
}

// 3. Also check: Are there other create methods available?
echo "\n=== TEST 3: Try aliexpress.trade.buy.placeorder (buyer API) ===\n";
$apiClient = app(AliExpressApiClient::class);
$res3 = $apiClient->call('aliexpress.trade.buy.placeorder', $accessToken, [
    'param_place_order_request4_open_api_d_t_o' => $dto,
]);
$body3 = $res3['body'] ?? [];
echo "aliexpress.trade.buy.placeorder response: " . json_encode([
    'ok' => $res3['ok'],
    'code' => $res3['code'] ?? null,
    'message' => $res3['message'] ?? null,
    'error_code' => $body3['error_response']['code'] ?? ($body3['aliexpress_trade_buy_placeorder_response']['result']['error_code'] ?? null),
    'error_msg' => $body3['error_response']['msg'] ?? ($body3['aliexpress_trade_buy_placeorder_response']['result']['error_msg'] ?? null),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== DONE ===\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_raw_request.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_raw_request.php && rm debug_raw_request.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
