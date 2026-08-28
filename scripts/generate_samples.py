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
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

app()->setLocale('ar');

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function generateCustPdf() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        echo "Processed customers count: " . $records->count() . "\\n";
        
        $html = view('admin::reporting.detailed.customers-pdf', [
            'records' => $records,
            'includeOrders' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        echo "HTML length: " . strlen($html) . "\\n";
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/cust_sample.html', $html);
        
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'autoPageBreak' => true,
            'autoMarginPadding' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        
        echo "Customers PDF pages: " . $mpdf->page . "\\n";
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/cust_sample.pdf', $mpdf->Output('', 'S'));
        echo "Wrote cust_sample.pdf to public\\n";
    }
    
    public function generateProdPdf() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $records = $this->getProcessedProducts($req, 'ar', $catTree);
        
        echo "Processed products count: " . $records->count() . "\\n";
        
        $html = view('admin::reporting.detailed.pdf', [
            'records' => $records,
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->translatedFormat('Y-m-d H:i:s'),
        ])->render();
        
        echo "Products HTML length: " . strlen($html) . "\\n";
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/prod_sample.html', $html);
        
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'autoPageBreak' => true,
            'autoMarginPadding' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        
        echo "Products PDF pages: " . $mpdf->page . "\\n";
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/prod_sample.pdf', $mpdf->Output('', 'S'));
        echo "Wrote prod_sample.pdf to public\\n";
    }
};

$controller->generateCustPdf();
$controller->generateProdPdf();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/generate_samples.php", 'w') as f:
    f.write(inspect_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php generate_samples.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/generate_samples.php")
client.close()
