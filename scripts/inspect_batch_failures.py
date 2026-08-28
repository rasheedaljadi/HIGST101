import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('external_platform_orders')
    ->whereIn('id', [67, 68, 69, 70, 71])
    ->get();

foreach ($rows as $r) {
    echo "ID: {$r->id}, PO: {$r->supplier_purchase_order_id}, Status: {$r->normalized_status}\n";
    echo "Failure Code: {$r->failure_code}\n";
    echo "Failure Message: {$r->failure_message}\n";
    echo "------------------------------------------------------------\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_batch_failures.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_batch_failures.php && rm inspect_batch_failures.php")
print(f"OUTPUT:\n{out}")

client.close()
