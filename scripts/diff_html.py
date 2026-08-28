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
    public function diffHtml() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $bladeHtml = view('admin::reporting.detailed.customers-pdf', [
            'records' => $records,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/blade_cust.html', $bladeHtml);
        
        // Let's test removing elements one by one from bladeHtml
        // 1. Full Blade HTML
        $m1 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m1->WriteHTML($bladeHtml);
        echo "1. Full Blade HTML => {$m1->page} pages\\n";
        
        // 2. Remove header table
        $noHeader = preg_replace('/<table class="header-table">.*?<\/table>/is', '', $bladeHtml);
        $m2 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m2->WriteHTML($noHeader);
        echo "2. No Header Table => {$m2->page} pages\\n";
        
        // 3. Remove <th> style attributes (width)
        $noThWidth = preg_replace('/<th[^>]*>/i', '<th>', $bladeHtml);
        $m3 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m3->WriteHTML($noThWidth);
        echo "3. No TH widths => {$m3->page} pages\\n";
        
        // 4. Remove all classes
        $noClasses = preg_replace('/class="[^"]*"/i', '', $bladeHtml);
        $m4 = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $m4->WriteHTML($noClasses);
        echo "4. No classes => {$m4->page} pages\\n";
    }
};

$controller->diffHtml();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/diff_html.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php diff_html.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/diff_html.php")
client.close()
