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

echo "=== SEARCHING ALL LOGISTICS / REGION / ADDRESS APIS ===\n";

$methods = [
    // 1. Address tree and region methods
    'aliexpress.logistics.address.tree.get' => ['country' => 'SA'],
    'aliexpress.logistics.region.query' => ['country' => 'SA'],
    'aliexpress.logistics.country.query' => ['country' => 'SA'],
    'aliexpress.trade.buy.placeorder' => ['country' => 'SA'],
    'aliexpress.ds.trade.order.create' => ['country' => 'SA'],
    'aliexpress.logistics.buyer.freight.query' => ['country' => 'SA'],
    'aliexpress.ds.freight.query' => [
        'queryDeliveryReq' => [
            'productId' => '1005010737996063',
            'shipToCountry' => 'SA',
            'quantity' => 1,
            'currency' => 'USD',
            'selectedSkuId' => '12000053357140815',
        ]
    ],
];

foreach ($methods as $m => $p) {
    echo "Calling {$m}...\n";
    $r = $apiClient->call($m, $accessToken, $p);
    echo "  Status: {$r['status']}, ok=" . ($r['ok'] ? 'true' : 'false') . ", code=" . ($r['code'] ?? 'none') . "\n";
    if (!empty($r['body']) && empty($r['body']['error_response'])) {
        echo "  RESPONSE: " . json_encode(array_slice($r['body'], 0, 2), JSON_UNESCAPED_UNICODE) . "\n";
    } elseif (!empty($r['body']['error_response'])) {
        echo "  ERROR: " . ($r['body']['error_response']['msg'] ?? $r['body']['error_response']['code']) . "\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/probe_all_endpoints.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 probe_all_endpoints.php && rm probe_all_endpoints.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
