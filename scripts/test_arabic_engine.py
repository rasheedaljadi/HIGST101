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
    public function testEngines() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $records = $this->getProcessedProducts($req, 'ar', $catTree);
        
        $html = view('admin::reporting.detailed.pdf', [
            'records' => $records->take(10),
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => ['فلتر تجريبي: نشط'],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        // 1. Test mPDF (Native Arabic Support)
        $t0 = microtime(true);
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 6,
            'margin_bottom' => 6,
            'autoPageBreak' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        $mpdfOut = $mpdf->Output('', 'S');
        $tMpdf = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_mpdf_arabic.pdf', $mpdfOut);
        echo "mPDF 10 records: {$tMpdf}s, pages: {$mpdf->page}, size: " . round(strlen($mpdfOut)/1024, 2) . " KB\\n";
        
        // 2. Test Full 471 records in mPDF with clean CSS
        $t0 = microtime(true);
        $htmlFull = view('admin::reporting.detailed.pdf', [
            'records' => $records,
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        $mpdfFull = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 6,
            'margin_bottom' => 6,
            'autoPageBreak' => true,
        ]);
        $mpdfFull->SetDirectionality('rtl');
        $mpdfFull->autoScriptToLang = true;
        $mpdfFull->autoLangToFont = true;
        $mpdfFull->WriteHTML($htmlFull);
        $mpdfFullOut = $mpdfFull->Output('', 'S');
        $tFull = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_mpdf_full.pdf', $mpdfFullOut);
        echo "mPDF FULL 471 records: {$tFull}s, pages: {$mpdfFull->page}, size: " . round(strlen($mpdfFullOut)/1024, 2) . " KB\\n";
    }
};

$controller->testEngines();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_arabic_engine.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_arabic_engine.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_arabic_engine.php")
client.close()
