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
use ArPHP\\I18N\\Arabic;

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function prepareDomPdfHtml(string $html): string
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $arabic = new Arabic();
        $positions = $arabic->arIdentify($html);
        for ($i = count($positions) - 1; $i >= 0; $i -= 2) {
            $segment = substr($html, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);
            $converted = $arabic->utf8Glyphs($segment);
            $html = substr_replace($html, $converted, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);
        }
        return $html;
    }

    public function testBothDomPdf() {
        // 1. Customers Test
        $t0 = microtime(true);
        $reqCust = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $custRecords = $this->getProcessedCustomers($reqCust);
        $htmlCust = view('admin::reporting.detailed.customers-pdf', [
            'records' => $custRecords,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();
        
        $preparedCust = $this->prepareDomPdfHtml($htmlCust);
        $pdfCust = DomPdf::loadHTML($preparedCust)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans');
        $outCust = $pdfCust->output();
        $tCust = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_cust_final.pdf', $outCust);
        $sizeCust = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_cust_final.pdf') / 1024, 2);
        echo "[CUSTOMERS] DomPDF: {$tCust}s, size: {$sizeCust} KB\\n";

        // 2. Products Test (All 471 parent products!)
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
        
        $preparedProd = $this->prepareDomPdfHtml($htmlProd);
        $pdfProd = DomPdf::loadHTML($preparedProd)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans');
        $outProd = $pdfProd->output();
        $tProd = round(microtime(true) - $t0, 2);
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod_final.pdf', $outProd);
        $sizeProd = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/dompdf_prod_final.pdf') / 1024, 2);
        echo "[PRODUCTS] DomPDF (471 items): {$tProd}s, size: {$sizeProd} KB\\n";
    }
};

$controller->testBothDomPdf();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_both_dompdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_both_dompdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_both_dompdf.php")
client.close()
