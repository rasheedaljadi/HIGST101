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

try {
    app()->setLocale('ar');
    $controller = app()->make(\\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);
    
    // Testing default PDF export
    $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
    $t0 = microtime(true);
    echo "Executing exportProducts(format=pdf)...\\n";
    $response = $controller->exportProducts($req);
    $elapsed = round(microtime(true) - $t0, 2);
    echo "SUCCESS! Response type: " . get_class($response) . " in {$elapsed}s\\n";
} catch (\\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_final_pdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php verify_final_pdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/verify_final_pdf.php")
client.close()
