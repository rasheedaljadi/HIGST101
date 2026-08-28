import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AliExpress\AliExpressOAuthService;

$oauth = app(AliExpressOAuthService::class);
$token = $oauth->latestToken();

echo "ACCESS_TOKEN_RAW:\n" . $token->access_token . "\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/get_plain_token.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 get_plain_token.php && rm get_plain_token.php")
print(f"OUTPUT:\n{out}")

client.close()
