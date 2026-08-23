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

$runs = DB::table('sync_runs')->orderBy('created_at', 'desc')->limit(3)->get();
foreach ($runs as $r) {
    echo "ID: {$r->id} | Status: {$r->status} | Started: {$r->started_at} | Completed: {$r->completed_at}\n";
    echo "Metadata: {$r->metadata}\n";
    echo "Statistics: {$r->statistics}\n";
    echo "--------------------------------------------------------\n";
}

$notif = DB::table('notifications')->where('type', 'scheduled_sync')->orderBy('id', 'desc')->limit(2)->get();
foreach ($notif as $n) {
    echo "Notif ID: {$n->id} | Title: {$n->title} | Created: {$n->created_at}\n";
}
'''

with sftp.file(f"{APP_DIR}/tmp_check_latest_run.php", "w") as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_check_latest_run.php && rm tmp_check_latest_run.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- LATEST RUN & NOTIFICATIONS ---")
print(out.encode('ascii', errors='replace').decode('ascii'))

client.close()
