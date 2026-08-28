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

use Barryvdh\\DomPDF\\Facade\\Pdf as DomPdf;

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function shapeArabicSafe($html) {
        if (class_exists(\\ArPHP\\I18N\\Arabic::class)) {
            $arabic = new \\ArPHP\\I18N\\Arabic();
            $p = $arabic->arIdentify($html);
            if (!empty($p)) {
                for ($i = count($p) - 1; $i >= 1; $i -= 2) {
                    $len = $p[$i] - $p[$i - 1];
                    if ($len > 0) {
                        $utf8Glyphs = $arabic->utf8Glyphs(substr($html, $p[$i - 1], $len));
                        $html = substr_replace($html, $utf8Glyphs, $p[$i - 1], $len);
                    }
                }
            }
        }
        return $html;
    }

    public function testProductsDomPdf() {
        $t0 = microtime(true);
        $reqProd = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $prodRecords = $this->getProcessedProducts($reqProd, 'ar', $catTree);
        
        $htmlProd = view('admin::reporting.detailed.pdf', [
            'records' => $prodRecords,
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();
        
        $shaped = $this->shapeArabicSafe($htmlProd);
        
        $pdf = DomPdf::loadHTML($shaped)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans')
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true);
            
        $output = $pdf->output();
        $t = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod.pdf', $output);
        $sizeKb = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod.pdf') / 1024, 2);
        
        echo "SUCCESS! DomPDF Products (471 products): generated in {$t}s, size: {$sizeKb} KB\\n";
    }
};

$controller->testProductsDomPdf();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_prod_dompdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_prod_dompdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_prod_dompdf.php")
client.close()
