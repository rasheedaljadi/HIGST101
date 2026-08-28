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

# 1. Read last 150 lines of log
run_cmd(f"tail -n 150 {APP_DIR}/storage/logs/laravel.log")

# 2. Test rendering sales report via PHP CLI script on remote server
php_test_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

try {
    echo "1. Testing SaleController@index...\\n";
    $controller = app(Webkul\\Admin\\Http\\Controllers\\Reporting\\SaleController::class);
    $request = Illuminate\\Http\\Request::create('/admin/reporting/sales', 'GET');
    $response = $controller->index($request);
    echo "Index Response: " . get_class($response) . "\\n";
    if (method_exists($response, 'render')) {
        $html = $response->render();
        echo "Render SUCCESS! HTML length: " . strlen($html) . "\\n";
    }
} catch (Throwable $e) {
    echo "ERROR IN SALE CONTROLLER INDEX: " . $e->getMessage() . "\\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\\n";
    echo "TRACE:\\n" . $e->getTraceAsString() . "\\n";
}

try {
    echo "\\n2. Testing Sale reporting stats...\\n";
    $statsTypes = [
        'total-sales', 'total-orders', 'average-sales', 'total-due',
        'abandoned-carts', 'total-tax', 'shipping-collected', 'refunds',
        'top-selling-products-by-revenue', 'top-selling-products-by-quantity',
        'top-payment-methods'
    ];
    foreach ($statsTypes as $type) {
        $req = Illuminate\\Http\\Request::create('/admin/reporting/sales/stats', 'GET', ['type' => $type]);
        $res = $controller->stats($req);
        echo "  - Stats [{$type}]: OK\\n";
    }
} catch (Throwable $e) {
    echo "ERROR IN SALE STATS: " . $e->getMessage() . "\\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/diagnose_sales.php", 'w') as f:
    f.write(php_test_code)
sftp.close()

run_cmd(f"cd {APP_DIR} && php diagnose_sales.php")
run_cmd(f"rm {APP_DIR}/diagnose_sales.php")

client.close()
