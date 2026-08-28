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

script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

// Set admin theme
themes()->set('admin');

// Share errors ViewErrorBag
view()->share('errors', new Illuminate\\Support\\ViewErrorBag);

// Authenticate
$admin = Webkul\\User\\Models\\Admin::first();
auth()->guard('admin')->setUser($admin);

echo "1. Testing Sales Report view render...\\n";
try {
    $saleController = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\SaleController::class);
    $req = Illuminate\\Http\\Request::create('/admin/reporting/sales', 'GET');
    $res = $saleController->index($req);
    $html = $res->render();
    echo "✓ Sales Report Render: SUCCESS (HTML Length: " . strlen($html) . ")\\n";
} catch (Throwable $e) {
    echo "✗ Sales Report FAILED: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\\n";
}

echo "\\n2. Testing Detailed Products Report view render...\\n";
try {
    $prodController = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);
    $req = Illuminate\\Http\\Request::create('/admin/detailed-reports/products', 'GET');
    $res = $prodController->products($req);
    $html = $res->render();
    echo "✓ Detailed Products Report Render: SUCCESS (HTML Length: " . strlen($html) . ")\\n";
    echo "  - Total records count: " . $res->getData()['records']->total() . "\\n";
} catch (Throwable $e) {
    echo "✗ Detailed Products Report FAILED: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\\n";
}

echo "\\n3. Testing Sales Report Stats API endpoint...\\n";
try {
    $req = Illuminate\\Http\\Request::create('/admin/reporting/sales/stats', 'GET', ['type' => 'total-sales']);
    $res = $saleController->stats($req);
    echo "✓ Sales Stats API: SUCCESS! " . json_encode($res->getData()) . "\\n";
} catch (Throwable $e) {
    echo "✗ Sales Stats API FAILED: " . $e->getMessage() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_reports_full.php", 'w') as f:
    f.write(script)
sftp.close()

run_cmd(f"cd {APP_DIR} && php verify_reports_full.php && rm verify_reports_full.php")

client.close()
