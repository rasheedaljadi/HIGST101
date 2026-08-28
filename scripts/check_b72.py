import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logFile = storage_path('logs/laravel.log');
$lines = file($logFile);
foreach (array_slice($lines, -200) as $l) {
    if (str_contains($l, 'ProcurementBatchController') || str_contains($l, 'submitBatch') || str_contains($l, 'submit/72') || str_contains($l, 'batches/submit')) {
        echo $l;
    }
}

echo "=== CHECKING BATCH 72 ===\n";
$b72 = DB::table('procurement_batches')->where('id', 72)->first();
print_r($b72);
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/check_b72.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 check_b72.php && rm check_b72.php")
print(f"OUTPUT:\n{out}")

client.close()
