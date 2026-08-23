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

php_test = r"""<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Illuminate\Support\Facades\Auth;

$admin = Admin::where('email', 'a@a.com')->first();
echo "Admin user: " . $admin->name . "\n";
echo "Role: " . $admin->role->name . " (type: " . $admin->role->permission_type . ")\n";
echo "Permissions in DB: " . json_encode($admin->role->permissions) . "\n";

Auth::guard('admin')->setUser($admin);

$menuItems = menu()->getItems('admin');
echo "Menu items count for Courier: " . count($menuItems) . "\n";
foreach ($menuItems as $item) {
    echo " - [" . $item->getKey() . "] " . $item->getName() . " -> children count: " . count($item->getChildren()) . "\n";
    foreach ($item->getChildren() as $c) {
        echo "    * [" . $c->getKey() . "] " . $c->getName() . " (" . $c->getUrl() . ")\n";
    }
}
"""

with sftp.open(f"{APP_DIR}/check_courier_menu.php", 'w') as f:
    f.write(php_test)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php check_courier_menu.php && rm check_courier_menu.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
