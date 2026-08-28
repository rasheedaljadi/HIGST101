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

$methods = [
    'aliexpress.logistics.redefining.getareas' => ['country' => 'SA'],
    'aliexpress.logistics.redefining.getchildareas' => ['area_id' => 'SA'],
    'aliexpress.ds.shipping.address.get' => [],
];

foreach ($methods as $m => $params) {
    echo "Calling {$m}...\n";
    $res = $apiClient->call($m, $latestToken->access_token, $params);
    echo "OK: " . ($res['ok'] ? 'YES' : 'NO') . "\n";
    print_r($res['body']);
    echo "----------------------------------------\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_areas.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_areas.php && rm test_areas.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
