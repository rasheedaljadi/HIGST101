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

// Let's check the size and pages of cust_sample.pdf
$pdfPath = '/home/highest-ye/htdocs/highest-ye.store/public/cust_sample.pdf';
echo "File size: " . filesize($pdfPath) . " bytes\\n";

// Let's test mPDF directly on a minimal 2-row table and check how many pages it creates
$html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>
body { font-family: dejavusans; font-size: 8pt; direction: rtl; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 4px; text-align: right; }
th { background: #eee; }
</style></head><body>
<h2>تقرير تجريبي</h2>
<table>
<thead><tr><th>المعرف</th><th>الاسم</th><th>البريد</th></tr></thead>
<tbody>
<tr><td>#1</td><td>عميل تجريبي 1</td><td>test1@example.com</td></tr>
<tr><td>#2</td><td>عميل تجريبي 2</td><td>test2@example.com</td></tr>
</tbody>
</table>
</body></html>';

$mpdf = new \\Mpdf\\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'orientation' => 'L',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
    'autoPageBreak' => true,
]);
$mpdf->SetDirectionality('rtl');
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;
$mpdf->WriteHTML($html);

echo "Minimal 2-row table pages: " . $mpdf->page . "\\n";

// Now test with customers-pdf view HTML
$controller = app()->make(\\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController::class);
$req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);

// Reflection to call getProcessedCustomers
$method = new \\ReflectionMethod($controller, 'getProcessedCustomers');
$method->setAccessible(true);
$records = $method->invoke($controller, $req);

$html2 = view('admin::reporting.detailed.customers-pdf', [
    'records' => $records,
    'includeOrders' => false,
    'logoUrl' => null,
    'activeFilterLabels' => [],
    'generatedAt' => now()->format('Y-m-d H:i:s'),
])->render();

$mpdf2 = new \\Mpdf\\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'orientation' => 'L',
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
    'autoPageBreak' => true,
]);
$mpdf2->SetDirectionality('rtl');
$mpdf2->autoScriptToLang = true;
$mpdf2->autoLangToFont = true;
$mpdf2->WriteHTML($html2);

echo "customers-pdf view with 25 customers pages: " . $mpdf2->page . "\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/inspect_mpdf_engine.php", 'w') as f:
    f.write(inspect_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_mpdf_engine.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/inspect_mpdf_engine.php")
client.close()
