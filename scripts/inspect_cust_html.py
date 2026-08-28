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

inspect_script = """<?php
require '/home/highest-ye/htdocs/highest-ye.store/vendor/autoload.php';
$app = require_once '/home/highest-ye/htdocs/highest-ye.store/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

app()->setLocale('ar');
$controller = app()->make(\\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);

$req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);

// Test Customers PDF view rendering
$custs = DB::table('customers')->limit(5)->get();
echo "Total customers count in DB: " . DB::table('customers')->count() . "\\n";

// Let's test customers-pdf view HTML
$html = view('admin::reporting.detailed.customers-pdf', [
    'records' => $custs,
    'includeOrders' => false,
    'logoUrl' => null,
    'activeFilterLabels' => [],
    'generatedAt' => now()->format('Y-m-d H:i:s'),
])->render();

echo "Rendered customers-pdf HTML length: " . strlen($html) . "\\n";
echo "First 500 chars of HTML:\\n" . substr($html, 0, 500) . "\\n";
echo "Last 500 chars of HTML:\\n" . substr($html, -500) . "\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/inspect_pdf_html.php", 'w') as f:
    f.write(inspect_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_pdf_html.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/inspect_pdf_html.php")
client.close()
