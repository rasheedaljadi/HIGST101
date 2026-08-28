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

echo "=== PROBING ALIEXPRESS ADDRESS API METHODS ===\n";

$methodsToTest = [
    'aliexpress.logistics.redefining.getlogisticsselleraddresses' => ['seller_address_query' => 'getdefault'],
    'aliexpress.logistics.redefining.getlogisticsselleraddresses' => ['seller_address_query' => 'sender'],
    'aliexpress.logistics.redefining.getlogisticsselleraddresses' => ['seller_address_query' => 'pickup'],
    'aliexpress.logistics.redefining.getlogisticsselleraddresses' => ['seller_address_query' => 'refund'],
    'aliexpress.logistics.buyer.freight.calculate' => ['country_code' => 'SA'],
    'aliexpress.ds.recommend.feed.get' => ['feed_name' => 'ds_popular'],
];

foreach ($methodsToTest as $method => $params) {
    echo "Testing method: {$method}...\n";
    $res = $apiClient->call($method, $accessToken, $params);
    echo "Result: ok=" . ($res['ok'] ? 'true' : 'false') . ", code=" . ($res['code'] ?? 'none') . "\n";
    if (!empty($res['body'])) {
        echo json_encode(array_slice($res['body'], 0, 3, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "----------------------------------------\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_address_methods.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_address_methods.php && rm probe_address_methods.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
