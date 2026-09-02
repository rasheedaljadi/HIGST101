import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Active Pricing Rules in higest_pricing_rules ===\n";
$rules = DB::table('higest_pricing_rules')->get();
foreach ($rules as $r) {
    print_r($r);
}

echo "\n=== AliExpress Settings in database ===\n";
$settings = DB::table('aliexpress_settings')->first();
print_r($settings);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_pricing_rules.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_pricing_rules.php && rm inspect_pricing_rules.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
