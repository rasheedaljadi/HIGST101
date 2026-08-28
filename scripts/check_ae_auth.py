import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressOAuthService;
use App\Services\AliExpress\AliExpressApiClient;
use Illuminate\Support\Facades\DB;

$oauth = app(AliExpressOAuthService::class);
$api = app(AliExpressApiClient::class);

echo "AliExpress API Config:\n";
echo "App Key: " . config('services.aliexpress.app_key') . "\n";
echo "App Secret: " . (config('services.aliexpress.app_secret') ? 'SET (' . strlen(config('services.aliexpress.app_secret')) . ' chars)' : 'NOT SET') . "\n";

$tokens = DB::table('aliexpress_oauth_tokens')->get();
echo "OAuth Tokens in DB: " . $tokens->count() . "\n";
foreach ($tokens as $t) {
    echo "ID: {$t->id}, User: {$t->user_nick}, Seller ID: {$t->seller_id}, Expires: {$t->expire_time}, Refresh Expires: {$t->refresh_token_valid_time}\n";
    echo "Access Token: " . substr($t->access_token, 0, 10) . "...\n";
}

$token = $oauth->getValidAccessToken();
echo "Resolved Valid Access Token: " . ($token ? substr($token, 0, 10) . '... (VALID)' : 'NONE/EXPIRED') . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_ae_auth.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_ae_auth.php && rm check_ae_auth.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
