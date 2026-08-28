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
    public function verifyAll() {
        // 1. Customer Report
        $reqCust = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $custRecords = $this->getProcessedCustomers($reqCust);
        $custHtml = view('admin::reporting.detailed.customers-pdf', [
            'records' => $custRecords,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        $mpdfCust = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $mpdfCust->SetDirectionality('rtl');
        $mpdfCust->autoScriptToLang = true;
        $mpdfCust->autoLangToFont = true;
        $mpdfCust->WriteHTML($custHtml);
        echo "Customer PDF verified: {$mpdfCust->page} page(s) for {$custRecords->count()} customers.\\n";
        
        // 2. Product Report
        $reqProd = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $prodRecords = $this->getProcessedProducts($reqProd, 'ar', $catTree);
        $prodHtml = view('admin::reporting.detailed.pdf', [
            'records' => $prodRecords,
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        $mpdfProd = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
        $mpdfProd->SetDirectionality('rtl');
        $mpdfProd->autoScriptToLang = true;
        $mpdfProd->autoLangToFont = true;
        $mpdfProd->WriteHTML($prodHtml);
        echo "Product PDF verified: {$mpdfProd->page} page(s) for {$prodRecords->count()} products.\\n";
    }
};

$controller->verifyAll();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/verify_all_pdfs.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php verify_all_pdfs.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/verify_all_pdfs.php")
client.close()
