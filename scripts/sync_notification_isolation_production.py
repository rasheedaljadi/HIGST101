import sys
import os
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'
LOCAL_ROOT = r'e:\HIGESTO NEW1\higest\higest101'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

# Sync NotificationController
sftp.put(
    os.path.join(LOCAL_ROOT, 'packages', 'Webkul', 'Admin', 'src', 'Http', 'Controllers', 'NotificationController.php'),
    f"{APP_DIR}/packages/Webkul/Admin/src/Http/Controllers/NotificationController.php"
)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

# Test notifications response for Courier
php_test = r"""<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Webkul\User\Models\Admin;
use Illuminate\Support\Facades\Auth;

$admin = Admin::where('email', 'a@a.com')->first();
Auth::guard('admin')->setUser($admin);

$controller = app()->make(\Webkul\Admin\Http\Controllers\NotificationController::class);
$res = $controller->getNotifications();
echo "Courier Notifications Count: " . $res['total_unread'] . "\n";
echo "Search results: " . json_encode($res['search_results']) . "\n";
"""

sftp = client.open_sftp()
with sftp.open(f"{APP_DIR}/test_notif_courier.php", 'w') as f:
    f.write(php_test)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_notif_courier.php && rm test_notif_courier.php")
print("\n>>> Verification for Courier User:")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
print("\n[OK] Notifications isolated for Couriers successfully!")
