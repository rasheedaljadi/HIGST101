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

def run_cmd(cmd):
    print(f"\n======================================")
    print(f">>> {cmd}")
    print(f"======================================")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out.strip())
    if err.strip():
        print("STDERR:", err.strip())

# 1. Check if physical directory exists in public/
run_cmd(f"ls -la {APP_DIR}/public/delivery 2>&1 || true")

# 2. Check Laravel's recent log entries
run_cmd(f"tail -n 50 {APP_DIR}/storage/logs/laravel.log 2>&1 || true")

# 3. Test router response for /delivery, /admin/courier, /admin/delivery directly in PHP
php_diag = """<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\\Contracts\\Http\\Kernel');

use Illuminate\\Http\\Request;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Support\\Facades\\Auth;

$admin = Admin::where('email', 'a@a.com')->first();
Auth::guard('admin')->setUser($admin);

$urls = ['/delivery', '/admin/courier', '/admin/delivery'];

foreach ($urls as $u) {
    $req = Request::create($u, 'GET');
    $req->headers->set('Accept', 'text/html');
    $res = $kernel->handle($req);
    echo "URL: $u => Status: " . $res->getStatusCode() . "\\n";
    if ($res->isRedirection()) {
        echo "   Redirect To: " . $res->headers->get('Location') . "\\n";
    } elseif ($res->getStatusCode() === 404) {
        echo "   404 NOT FOUND!\\n";
    } else {
        echo "   OK! Content length: " . strlen($res->getContent()) . "\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.open(f"{APP_DIR}/diag_routes.php", 'w') as f:
    f.write(php_diag)
sftp.close()

run_cmd(f"cd {APP_DIR} && php diag_routes.php && rm diag_routes.php")

# 4. Check routes in route:list
run_cmd(f"cd {APP_DIR} && php artisan route:list | grep -E 'delivery|courier'")

client.close()
