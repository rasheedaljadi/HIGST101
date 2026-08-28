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

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function debugExact() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $blade = view('admin::reporting.detailed.customers-pdf', [
            'records' => $records,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        // Let us strip everything except <table> ... </table> from $blade
        preg_match('/<table class="data-table">.*?<\/table>/is', $blade, $match);
        $onlyTable = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>
            body { font-family: dejavusans; font-size: 7pt; direction: rtl; }
            table { width: 100%; border-collapse: collapse; font-size: 7pt; }
            th, td { border: 1px solid #ccc; padding: 2px; text-align: right; }
        </style></head><body>' . ($match[0] ?? '') . '</body></html>';
        
        $m1 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m1->SetDirectionality('rtl');
        $m1->autoScriptToLang = true;
        $m1->autoLangToFont = true;
        $m1->WriteHTML($onlyTable);
        echo "1. Only <table> from Blade with simple CSS => {$m1->page} pages\\n";
        
        // Let us test $blade without the Blade <style> tag
        $noStyle = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '<style>
            body { font-family: dejavusans; font-size: 7pt; direction: rtl; }
            table { width: 100%; border-collapse: collapse; font-size: 7pt; }
            th, td { border: 1px solid #ccc; padding: 2px; text-align: right; }
        </style>', $blade);
        $m2 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m2->SetDirectionality('rtl');
        $m2->autoScriptToLang = true;
        $m2->autoLangToFont = true;
        $m2->WriteHTML($noStyle);
        echo "2. Blade HTML with simple CSS => {$m2->page} pages\\n";
    }
};

$controller->debugExact();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/debug_exact.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php debug_exact.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/debug_exact.php")
client.close()
