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

script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

themes()->set('admin');
view()->share('errors', new Illuminate\\Support\\ViewErrorBag);
$admin = Webkul\\User\\Models\\Admin::first();
auth()->guard('admin')->setUser($admin);

try {
    $prodController = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);
    $req = Illuminate\\Http\\Request::create('/admin/detailed-reports/products', 'GET');
    $res = $prodController->products($req);
    $html = $res->render();
    echo "✓ Detailed Products Report Render: SUCCESS (HTML Length: " . strlen($html) . ")\\n";
} catch (Throwable $e) {
    echo "✗ Detailed Products Report FAILED: " . $e->getMessage() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_view.php", 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php verify_view.php && rm verify_view.php")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
