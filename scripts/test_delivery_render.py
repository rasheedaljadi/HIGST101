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

php_test = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Webkul\\User\\Models\\Admin;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Support\\Facades\\Request;

$admin = Admin::where('email', 'a@a.com')->first();
echo "User: {$admin->name} ({$admin->email}), role: {$admin->role->name}\\n";

Auth::guard('admin')->setUser($admin);

try {
    $controller = app()->make(Webkul\\DeliveryManagement\\Http\\Controllers\\DeliveryAgentController::class);
    $view = $controller->index(request());
    $rendered = $view->render();
    echo "Rendered View Successfully! HTML Size: " . strlen($rendered) . " bytes\\n";
} catch (\\Throwable $e) {
    echo "ERROR during view render: " . $e->getMessage() . "\\n";
    echo $e->getTraceAsString() . "\\n";
}
"""

with sftp.open(f"{APP_DIR}/test_delivery_render.php", 'w') as f:
    f.write(php_test)

sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_delivery_render.php && rm test_delivery_render.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
