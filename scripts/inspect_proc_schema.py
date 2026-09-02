import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Columns of procurement_audit_logs ===\n";
print_r(Schema::getColumnListing('procurement_audit_logs'));

echo "\n=== Latest 5 logs from procurement_audit_logs ===\n";
$logs = DB::table('procurement_audit_logs')->orderBy('id', 'desc')->take(5)->get();
print_r($logs);

echo "\n=== Columns of external_platform_orders ===\n";
print_r(Schema::getColumnListing('external_platform_orders'));

echo "\n=== Latest 5 external_platform_orders ===\n";
$ex = DB::table('external_platform_orders')->orderBy('id', 'desc')->take(5)->get();
print_r($ex);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_proc_schema.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php inspect_proc_schema.php && rm inspect_proc_schema.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
