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

files_to_sync = [
    ('packages/Webkul/DeliveryManagement/src/Config/acl.php', 'packages/Webkul/DeliveryManagement/src/Config/acl.php'),
    ('packages/Webkul/DeliveryManagement/src/Resources/lang/ar/app.php', 'packages/Webkul/DeliveryManagement/src/Resources/lang/ar/app.php'),
    ('packages/Webkul/DeliveryManagement/src/Resources/lang/en/app.php', 'packages/Webkul/DeliveryManagement/src/Resources/lang/en/app.php'),
    ('packages/Webkul/Admin/src/Http/Controllers/User/SessionController.php', 'packages/Webkul/Admin/src/Http/Controllers/User/SessionController.php'),
    ('packages/Webkul/User/src/Http/Middleware/Bouncer.php', 'packages/Webkul/User/src/Http/Middleware/Bouncer.php'),
]

for src, dst in files_to_sync:
    local_path = os.path.join(LOCAL_ROOT, src.replace('/', os.sep))
    remote_path = f"{APP_DIR}/{dst}"
    print(f"Uploading {src} -> {dst}")
    sftp.put(local_path, remote_path)

# Verify Role 25
php_check = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$courierRole = DB::table('roles')->where('id', 25)->first();
echo "Role ID 25 Name: {$courierRole->name} | Permissions: {$courierRole->permissions}\\n";

$admin = DB::table('admins')->where('id', 1378)->first();
echo "Courier Admin: {$admin->name} ({$admin->email}) | Role ID: {$admin->role_id}\\n";
"""

with sftp.open(f"{APP_DIR}/verify_courier.php", 'w') as f:
    f.write(php_check)

sftp.close()

def run_cmd(cmd):
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    print(out.strip())

run_cmd(f"cd {APP_DIR} && php verify_courier.php && rm verify_courier.php")
run_cmd(f"cd {APP_DIR} && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache")

client.close()
print("\n[OK] Courier login fix synchronized and production caches rebuilt!")
