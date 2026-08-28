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
    public function pinpoint() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $blade = view('admin::reporting.detailed.customers-pdf', [
            'records' => $records,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        // A. Without header-table
        $noHeaderTable = preg_replace('/<table class="header-table">.*?<\/table>/is', '', $blade);
        $m1 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m1->SetDirectionality('rtl');
        $m1->autoScriptToLang = true;
        $m1->autoLangToFont = true;
        $m1->WriteHTML($noHeaderTable);
        echo "A. Without header-table => {$m1->page} pages\\n";
        
        // B. With simple <div> header instead of header-table
        $divHeader = preg_replace('/<table class="header-table">.*?<\/table>/is', '<div style="border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 8px;"><h2>تقرير العملاء</h2></div>', $blade);
        $m2 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m2->SetDirectionality('rtl');
        $m2->autoScriptToLang = true;
        $m2->autoLangToFont = true;
        $m2->WriteHTML($divHeader);
        echo "B. With simple <div> header => {$m2->page} pages\\n";
    }
};

$controller->pinpoint();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/pinpoint.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php pinpoint.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/pinpoint.php")
client.close()
