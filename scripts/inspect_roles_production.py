import sys
import paramiko

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\DB;

echo "=== ROLES IN PRODUCTION DB ===\\n";
$roles = DB::table('roles')->get();
foreach ($roles as $r) {
    echo "ID: {$r->id} | Name: {$r->name} | Permission Type: {$r->permission_type} | Permissions: {$r->permissions}\\n";
}

echo "\\n=== COURIER / ADMIN USERS IN PRODUCTION DB ===\\n";
$admins = DB::table('admins')->get();
foreach ($admins as $a) {
    echo "ID: {$a->id} | Name: {$a->name} | Email: {$a->email} | Role ID: {$a->role_id} | Status: {$a->status}\\n";
}
"""

with sftp.open(f"{APP_DIR}/debug_roles.php", 'w') as f:
    f.write(php_script)

sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php debug_roles.php && rm debug_roles.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))
client.close()
