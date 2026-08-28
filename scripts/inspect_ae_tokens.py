import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== aliexpress_settings ===\n";
print_r(DB::table('aliexpress_settings')->get()->toArray());

echo "=== aliexpress_tokens ===\n";
print_r(DB::table('aliexpress_tokens')->get()->toArray());

echo "=== provider_accounts ===\n";
print_r(DB::table('provider_accounts')->get()->toArray());
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_ae_tokens.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_ae_tokens.php && rm inspect_ae_tokens.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
