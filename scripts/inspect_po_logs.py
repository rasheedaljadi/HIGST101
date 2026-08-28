import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logs = DB::table('external_platform_orders')->whereIn('id', [63, 64, 65, 66])->get();
print_r($logs);

// Look at storage/logs/laravel.log recent entries for Procurement
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -100);
    echo "=== RECENT LARAVEL LOG ENTRIES ===\n";
    foreach ($recent as $l) {
        if (str_contains($l, 'Procurement') || str_contains($l, 'AliExpress') || str_contains($l, 'submit') || str_contains($l, 'ERROR') || str_contains($l, 'WARNING')) {
            echo substr($l, 0, 300) . "\n";
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_po_logs.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_po_logs.php && rm inspect_po_logs.php")
print(f"OUTPUT:\n{out}")

client.close()
