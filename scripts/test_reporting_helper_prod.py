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

php_test = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

$helper = app(Webkul\\Admin\\Helpers\\Reporting::class);

$methods = [
    'getTotalSalesStats',
    'getAverageSalesStats',
    'getTotalOrdersStats',
    'getPurchaseFunnelStats',
    'getAbandonedCartsStats',
    'getRefundsStats',
    'getTaxCollectedStats',
    'getShippingCollectedStats',
    'getTopPaymentMethods',
    'getTopSellingProductsByRevenue',
    'getTopSellingProductsByQuantity',
    'getTopCustomersByRevenue',
    'getTopCustomersByOrderCount',
];

echo "=== TESTING ALL REPORTING HELPER METHODS ===\\n";
foreach ($methods as $method) {
    try {
        if (!method_exists($helper, $method)) {
            echo "✗ Method NOT found: {$method}\\n";
            continue;
        }
        $res = $helper->{$method}();
        echo "✓ {$method}: OK (Type: " . gettype($res) . ")\\n";
    } catch (Throwable $e) {
        echo "✗ ERROR in {$method}: " . $e->getMessage() . "\\n";
        echo "  FILE: " . $e->getFile() . ":" . $e->getLine() . "\\n";
        echo "  SQL / Trace:\\n" . $e->getTraceAsString() . "\\n\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_reporting_helper.php", 'w') as f:
    f.write(php_test)
sftp.close()

run_cmd(f"cd {APP_DIR} && php test_reporting_helper.php")
run_cmd(f"rm {APP_DIR}/test_reporting_helper.php")

client.close()
