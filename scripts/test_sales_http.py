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
    print(f"\n>>> {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out.strip())
    if err.strip():
        print("STDERR:\n" + err.strip())

php_http_test = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$request = Illuminate\\Http\\Request::create('/admin/reporting/sales', 'GET');
$kernel = $app->make(Illuminate\\Contracts\\Http\\Kernel::class);

$response = $kernel->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\\n";
if ($response->getStatusCode() >= 400) {
    echo "Content Preview:\\n" . substr($response->getContent(), 0, 1500) . "\\n";
} else {
    echo "SUCCESS! Content length: " . strlen($response->getContent()) . "\\n";
}

echo "\\n--- Checking latest 30 lines of laravel.log ---\\n";
if (file_exists('storage/logs/laravel.log')) {
    $lines = file('storage/logs/laravel.log');
    echo implode('', array_slice($lines, -30));
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_http_sales.php", 'w') as f:
    f.write(php_http_test)
sftp.close()

run_cmd(f"cd {APP_DIR} && php test_http_sales.php")
run_cmd(f"rm {APP_DIR}/test_http_sales.php")

client.close()
