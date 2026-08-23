import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

php_script = r'''<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== RECENT NOTIFICATIONS ===\n";
if (Schema::hasTable('notifications')) {
    $notifications = DB::table('notifications')
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get();
    foreach ($notifications as $n) {
        echo "ID: {$n->id} | Type: {$n->type} | Title: " . ($n->title ?? 'N/A') . " | Created: {$n->created_at}\n";
        echo "Message: " . ($n->message ?? 'N/A') . "\n";
        echo "Data: " . ($n->data ?? 'N/A') . "\n";
        echo "--------------------------------------------------\n";
    }
}

echo "\n=== SYNC RUNS / FULFILLMENT SYNC TABLES ===\n";
$tables = ['sync_runs', 'fulfillment_sync_runs', 'dropship_sync_runs', 'aliexpress_sync_runs'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "Found table: {$table}\n";
        $runs = DB::table($table)->orderBy('id', 'desc')->limit(5)->get();
        foreach ($runs as $r) {
            echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

echo "\n=== RECENT FAILED JOBS ===\n";
if (Schema::hasTable('failed_jobs')) {
    $failedJobs = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(5)->get();
    foreach ($failedJobs as $fj) {
        echo "ID: {$fj->id} | Connection: {$fj->connection} | Queue: {$fj->queue} | Failed At: {$fj->failed_at}\n";
        echo "Exception: " . substr($fj->exception, 0, 500) . "\n";
        echo "--------------------------------------------------\n";
    }
}
'''

with sftp.file(f"{APP_DIR}/tmp_check_sync_fail.php", "w") as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_check_sync_fail.php && rm tmp_check_sync_fail.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- SYNC FAILURE INVESTIGATION ---")
print(out)
if err:
    print("--- ERROR ---")
    print(err)

client.close()
