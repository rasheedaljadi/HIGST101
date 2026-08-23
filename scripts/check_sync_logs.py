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

echo "=== LATEST 5 SYNC RUNS ===\n";
$runs = DB::table('sync_runs')->orderBy('created_at', 'desc')->limit(5)->get();
foreach ($runs as $r) {
    echo "ID: {$r->id}\n";
    echo "Status: {$r->status}\n";
    echo "Started: {$r->started_at} | Completed: {$r->completed_at}\n";
    echo "Metadata: {$r->metadata}\n";
    echo "Statistics: {$r->statistics}\n";
    echo "Health: {$r->health_snapshot}\n";
    echo "--------------------------------------------------------\n";
}

echo "\n=== ALIEXPRESS SYNC SETTINGS & TOKENS ===\n";
if (\Illuminate\Support\Facades\Schema::hasTable('aliexpress_tokens')) {
    $token = DB::table('aliexpress_tokens')->latest('id')->first();
    if ($token) {
        echo "Token User: " . ($token->user_nick ?? $token->seller_id ?? 'N/A') . "\n";
        echo "Token Expires: " . ($token->expire_time ?? $token->expires_at ?? 'N/A') . "\n";
        echo "Token Updated: " . ($token->updated_at ?? 'N/A') . "\n";
    } else {
        echo "No AliExpress token found in database.\n";
    }
}

if (\Illuminate\Support\Facades\Schema::hasTable('aliexpress_settings')) {
    $settings = DB::table('aliexpress_settings')->first();
    echo "AliExpress Settings: " . json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
'''

with sftp.file(f"{APP_DIR}/tmp_check_sync_details.php", "w") as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_check_sync_details.php && rm tmp_check_sync_details.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- SYNC RUNS AND SETTINGS ---")
print(out)
if err:
    print("--- ERROR ---")
    print(err)

# Also check aliexpress.log or laravel.log tail
stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && tail -n 100 storage/logs/aliexpress.log 2>/dev/null || tail -n 100 storage/logs/laravel.log")
log_out = stdout.read().decode('utf-8', errors='replace').strip()
print("\n--- RECENT LOG ENTRIES ---")
print(log_out[-3000:])

client.close()
