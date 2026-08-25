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
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use Webkul\\Procurement\\Models\\ProcurementDemand;

$totalImports = AliExpressProductImport::where('status', 'success')->count();
$demands = ProcurementDemand::where('order_id', 313)->orWhere('id', 36)->get();

echo "Total Imports: {$totalImports}\\n";
echo "Demand #36 state: " . $demands->first()?->state . "\\n";
echo "Demand #36 store_id: " . $demands->first()?->supplier_store_id . "\\n";
echo "Demand #36 store_name: " . $demands->first()?->supplier_store_name . "\\n";
"""

with sftp.open(f"{APP_DIR}/check_status_quick.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php check_status_quick.php && rm check_status_quick.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
