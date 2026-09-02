import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;
use Carbon\\Carbon;

$logPath = storage_path('logs/aliexpress-2026-08-30.log');
$lines = file($logPath);

$syncStarted = [];
$syncCompleted = [];
$syncSkipped = [];
$apiCalls = [];

foreach ($lines as $line) {
    if (str_contains($line, '2026-08-30 02:39:') || str_contains($line, '2026-08-30 02:40:') || str_contains($line, '2026-08-30 02:41:')) {
        if (str_contains($line, 'AliExpress sync started')) {
            $syncStarted[] = $line;
        } elseif (str_contains($line, 'AliExpress sync completed')) {
            $syncCompleted[] = $line;
        } elseif (str_contains($line, 'AliExpress sync skipped')) {
            $syncSkipped[] = $line;
        } elseif (str_contains($line, 'AliExpress API call')) {
            $apiCalls[] = $line;
        }
    }
}

echo "=========================================================\\n";
echo "SUMMARY STATS OF MANUAL SYNC RUN (02:39 -> 02:41)\\n";
echo "=========================================================\\n";
echo "Total Products Checked / Started: " . count($syncStarted) . "\\n";
echo "Total Products Updated (Changes Published): " . count($syncCompleted) . "\\n";
echo "Total Products Skipped (No Changes / Hash Matched): " . count($syncSkipped) . "\\n";
echo "Total API Calls to AliExpress: " . count($apiCalls) . "\\n";

echo "\\n--- [ SAMPLE OF UPDATED PRODUCTS ] ---\\n";
foreach (array_slice($syncCompleted, 0, 10) as $c) {
    echo "  " . trim(substr($c, 0, 150)) . "\\n";
}

echo "\\n--- [ SAMPLE OF SKIPPED PRODUCTS (NO CHANGES) ] ---\\n";
foreach (array_slice($syncSkipped, 0, 5) as $s) {
    echo "  " . trim(substr($s, 0, 150)) . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/sync_breakdown.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 sync_breakdown.php && rm sync_breakdown.php")
print(f"OUT:\n{out}")

client.close()
