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
@ini_set('memory_limit', '-1');
@ini_set('pcre.backtrack_limit', '100000000');
@ini_set('pcre.recursion_limit', '100000000');
@set_time_limit(300);

require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    app()->setLocale('ar');
    $controller = app()->make(\\Webkul\\Admin\\Http\Controllers\\Reporting\\DetailedReportController::class);
    $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
    
    echo "Calling exportProducts on Production...\\n";
    $t0 = microtime(true);
    $response = $controller->exportProducts($req);
    $elapsed = round(microtime(true) - $t0, 2);
    echo "SUCCESS! Response class: " . get_class($response) . " in {$elapsed}s\\n";
} catch (\\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\\n";
    echo "FILE: " . $e->getFile() . " LINE: " . $e->getLine() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_prod_pdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_prod_pdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

# Cleanup
client.exec_command(f"rm {APP_DIR}/test_prod_pdf.php")
client.close()
