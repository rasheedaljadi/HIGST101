import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;

$token = DB::table('aliexpress_tokens')->orderBy('id', 'desc')->first();
print_r($token);

// Try refreshing the token to get updated Online scopes
$oauth = app(AliExpressOAuthService::class);
try {
    echo "\nRefreshing token...\n";
    $refreshed = $oauth->refreshToken($token->refresh_token);
    print_r($refreshed);
} catch (\Throwable $e) {
    echo "Refresh error: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_token_refresh.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_token_refresh.php && rm check_token_refresh.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
