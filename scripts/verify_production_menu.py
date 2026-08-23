import paramiko

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=15)
sftp = client.open_sftp()

php_test = r'''<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = \Webkul\User\Models\Admin::first();
if ($admin) {
    auth()->guard('admin')->login($admin);
}

$menuItems = menu()->getItems('admin');
$keys = [];
foreach ($menuItems as $item) {
    $keys[] = $item->getKey() . ' -> ' . $item->getName();
}

echo "ADMIN_SIDEBAR_MENU_ITEMS:\n" . implode("\n", $keys) . "\n";
'''

with sftp.file(f"{APP_DIR}/tmp_test_menu.php", "w") as f:
    f.write(php_test)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php tmp_test_menu.php && rm tmp_test_menu.php")
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()

print("--- SIDEBAR MENU VERIFICATION ---")
print(out)
if err:
    print("--- ERROR ---")
    print(err)

client.close()
