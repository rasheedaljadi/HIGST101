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

test_script = """<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

app()->setLocale('ar');
$controller = app()->make(\\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);

echo "--- 1. Testing Live Customer PDF Export ---\\n";
$t0 = microtime(true);
$reqCust = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
$resCust = $controller->exportCustomers($reqCust);
$elapsedCust = round(microtime(true) - $t0, 2);
echo "[SUCCESS] Customer PDF Export: " . get_class($resCust) . " in {$elapsedCust}s\\n";

echo "--- 2. Testing Live Product PDF Export (All 471 parent products) ---\\n";
$t0 = microtime(true);
$reqProd = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
$resProd = $controller->exportProducts($reqProd);
$elapsedProd = round(microtime(true) - $t0, 2);
echo "[SUCCESS] Product PDF Export: " . get_class($resProd) . " in {$elapsedProd}s\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_live_final.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php verify_live_final.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/verify_live_final.php")
client.close()
