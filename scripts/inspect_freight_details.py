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

echo "=== INSPECTING FREIGHT & PRODUCT QUERY FOR LOCATION TREE DATA ===\n";

$productId = '1005010737996063';
$skuId = '12000053357140815';

$freightRes = $apiClient->call('aliexpress.ds.freight.query', $accessToken, [
    'queryDeliveryReq' => [
        'productId' => $productId,
        'shipToCountry' => 'SA',
        'quantity' => 1,
        'currency' => 'USD',
        'language' => 'en_US',
        'locale' => 'en_US',
        'selectedSkuId' => $skuId,
    ],
]);

echo "Freight Response:\n" . json_encode($freightRes['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_freight_details.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_freight_details.php && rm inspect_freight_details.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
