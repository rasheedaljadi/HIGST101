import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\AliExpressAuthorizationResolver;
use App\Models\AliExpressToken;

$latest = AliExpressToken::latest()->first();
if ($latest) {
    echo "Latest Token ID: {$latest->id}\n";
    echo "Account: {$latest->account}\n";
    echo "Seller ID: {$latest->seller_id}\n";
    echo "Access Token Expires At: {$latest->access_token_expires_at}\n";
    echo "Is Access Token Valid: " . ($latest->isAccessTokenValid() ? 'YES' : 'NO') . "\n";
    echo "Is Refresh Token Valid: " . ($latest->isRefreshTokenValid() ? 'YES' : 'NO') . "\n";
}

$resolver = app(AliExpressAuthorizationResolver::class);
try {
    $auth = $resolver->resolveForDropshipperSubmission();
    echo "Auth resolved successfully! Account: {$auth->accountMasked}, Seller: {$auth->sellerId}, Expires: {$auth->expiresAt}\n";
} catch (\Throwable $e) {
    echo "Auth resolve failed: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_ae_auth_resolver.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_ae_auth_resolver.php && rm test_ae_auth_resolver.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
