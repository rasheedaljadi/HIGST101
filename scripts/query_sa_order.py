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
$apiClient = app(AliExpressApiClient::class);

$orderId = '1122492188571333';

echo "=== QUERYING ORDER {$orderId} FROM ALIEXPRESS ===\n";

$res = $apiClient->call('aliexpress.trade.ds.order.get', $token->access_token, [
    'single_order_query' => json_encode(['order_id' => $orderId]),
]);

$body = $res['body'] ?? [];
$resp = $body['aliexpress_trade_ds_order_get_response'] ?? $body;
$result = $resp['result'] ?? [];

echo "Order Status: " . ($result['order_status'] ?? 'UNKNOWN') . "\n";
echo "Pay Timeout: " . ($result['pay_timeout_second'] ?? 'N/A') . " seconds\n";
echo "Order Amount: " . ($result['order_amount']['amount'] ?? 'N/A') . " " . ($result['order_amount']['currency_code'] ?? '') . "\n";
echo "Logistics Info: " . json_encode($result['logistics_info_list'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "Receipt Address: " . json_encode($result['receipt_address'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/query_sa_order.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 query_sa_order.php && rm query_sa_order.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
