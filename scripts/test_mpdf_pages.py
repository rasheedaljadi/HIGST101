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

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function testPdf() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $records = $this->getProcessedProducts($req, 'ar', $catTree);
        
        echo "Total parent records: " . $records->count() . "\\n";
        
        $html = view('admin::reporting.detailed.pdf', [
            'records' => $records->take(10),
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => ['اختبار'],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();
        
        echo "HTML length for 10 records: " . strlen($html) . "\\n";
        
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 4,
            'margin_bottom' => 4,
            'autoPageBreak' => true,
            'simpleTables' => true,
            'packTableData' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        
        echo "Page count for 10 records: " . $mpdf->page . "\\n";
        
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_10.pdf', $mpdf->Output('', 'S'));
        echo "Wrote test_10.pdf to public\\n";
    }
};

$controller->testPdf();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_mpdf_pages.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_mpdf_pages.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_mpdf_pages.php")
client.close()
