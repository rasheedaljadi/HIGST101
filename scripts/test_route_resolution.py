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
$kernel = $app->make('Illuminate\\Contracts\\Http\\Kernel');

use Illuminate\\Http\\Request;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Support\\Facades\\Auth;

$admin = Admin::where('email', 'a@a.com')->first();
Auth::guard('admin')->setUser($admin);

$routesToTest = [
    '/admin/courier',
    '/delivery',
    '/admin/delivery',
    '/admin/dashboard'
];

foreach ($routesToTest as $path) {
    $request = Request::create($path, 'GET');
    $request->headers->set('Accept', 'text/html');
    $response = $kernel->handle($request);
    echo "Path: {$path} => Status: " . $response->getStatusCode();
    if ($response->isRedirection()) {
        echo " -> Redirect: " . $response->headers->get('Location');
    }
    echo "\\n";
}
"""

with sftp.open(f"{APP_DIR}/test_route_resolution.php", 'w') as f:
    f.write(php_test)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_route_resolution.php && rm test_route_resolution.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.close()
