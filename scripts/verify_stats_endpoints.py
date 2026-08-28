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

$statsTypes = [
    'total-sales', 'average-sales', 'total-orders', 'purchase-funnel',
    'abandoned-carts', 'refunds', 'tax-collected', 'shipping-collected',
    'top-payment-methods'
];

$saleController = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\SaleController::class);

echo "Testing all 9 Stats API endpoints on production:\\n";
foreach ($statsTypes as $type) {
    $req = Illuminate\\Http\\Request::create('/admin/reporting/sales/stats', 'GET', ['type' => $type]);
    app()->instance('request', $req);
    $res = $saleController->stats();
    $data = $res->getData(true);
    echo "✓ Stats [{$type}]: OK (HTTP {$res->getStatusCode()})\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_all_stats.php", 'w') as f:
    f.write(script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php verify_all_stats.php && rm verify_all_stats.php")
print(stdout.read().decode('utf-8', errors='replace'))

client.close()
