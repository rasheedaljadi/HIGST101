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

// Set theme to admin
$themes = app('Webkul\\Theme\\Themes');
$themes->set('admin');

// Authenticate as admin
$admin = Webkul\\User\\Models\\Admin::first();
auth()->guard('admin')->setUser($admin);

try {
    $helper = app(Webkul\\Admin\\Helpers\\Reporting::class);
    $view = view('admin::reporting.sales.index', [
        'startDate' => $helper->getStartDate(),
        'endDate' => $helper->getEndDate(),
    ]);
    
    $html = $view->render();
    echo "✓ VIEW RENDER SUCCESS! Length: " . strlen($html) . "\\n";
} catch (Throwable $e) {
    echo "✗ ERROR RENDERING VIEW: " . $e->getMessage() . "\\n";
    echo "  FILE: " . $e->getFile() . ":" . $e->getLine() . "\\n";
    echo "  TRACE:\\n" . $e->getTraceAsString() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_render_sales.php", 'w') as f:
    f.write(script)
sftp.close()

run_cmd(f"cd {APP_DIR} && php test_render_sales.php")
run_cmd(f"rm {APP_DIR}/test_render_sales.php")

client.close()
