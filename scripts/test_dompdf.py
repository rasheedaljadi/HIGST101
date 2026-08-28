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
    public function shapeArabic($html) {
        if (class_exists(\\ArPHP\\I18N\\Arabic::class)) {
            $arabic = new \\ArPHP\\I18N\\Arabic();
            $p = $arabic->arIdentify($html);
            for ($i = count($p) - 1; $i >= 0; $i -= 2) {
                $utf8Glyphs = $arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
                $html = substr_replace($html, $utf8Glyphs, $p[$i - 1], $p[$i] - $p[$i - 1]);
            }
        }
        return $html;
    }

    public function testDomPdfGeneration() {
        // 1. Customers Test
        $reqCust = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $custRecords = $this->getProcessedCustomers($reqCust);
        
        $t0 = microtime(true);
        $htmlCust = view('admin::reporting.detailed.customers-pdf', [
            'records' => $custRecords,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();
        
        $shapedCustHtml = $this->shapeArabic($htmlCust);
        
        $pdfCust = DomPdf::loadHTML($shapedCustHtml)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans')
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true);
            
        $custOutput = $pdfCust->output();
        $tCust = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_cust.pdf', $custOutput);
        $custSizeKb = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_cust.pdf') / 1024, 2);
        
        echo "DomPDF Customers (25 items): generated in {$tCust}s, size: {$custSizeKb} KB\\n";
        
        // 2. Products Test (All 471 products!)
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
        
        $shapedProdHtml = $this->shapeArabic($htmlProd);
        
        $pdfProd = DomPdf::loadHTML($shapedProdHtml)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans')
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true);
            
        $prodOutput = $pdfProd->output();
        $tProd = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod.pdf', $prodOutput);
        $prodSizeKb = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod.pdf') / 1024, 2);
        
        echo "DomPDF Products (471 items): generated in {$tProd}s, size: {$prodSizeKb} KB\\n";
    }
};

$controller->testDomPdfGeneration();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_dompdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_dompdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_dompdf.php")
client.close()
