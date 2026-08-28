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
    public function shapeSafe(string $html): string
    {
        if (! class_exists(Arabic::class)) {
            return $html;
        }

        $arabic = new Arabic();

        // Shape Arabic text inside HTML without crashing on edge cases
        return preg_replace_callback('/[\\x{0600}-\\x{06FF}\\x{0750}-\\x{077F}\\x{08A0}-\\x{08FF}\\x{FB50}-\\x{FDFF}\\x{FE70}-\\x{FEFF}][\\x{0600}-\\x{06FF}\\x{0750}-\\x{077F}\\x{08A0}-\\x{08FF}\\x{FB50}-\\x{FDFF}\\x{FE70}-\\x{FEFF}\\s\\d\\p{P}]*/u', function ($match) use ($arabic) {
            $str = $match[0];
            try {
                return $arabic->utf8Glyphs($str);
            } catch (\\Throwable $e) {
                return $str;
            }
        }, $html);
    }

    public function runTest() {
        $t0 = microtime(true);
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $records = $this->getProcessedProducts($req, 'ar', $catTree);
        
        $html = view('admin::reporting.detailed.pdf', [
            'records' => $records,
            'includeVariants' => false,
            'logoUrl' => null,
            'activeFilterLabels' => [],
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();
        
        $shaped = $this->shapeSafe($html);
        
        $pdf = DomPdf::loadHTML($shaped)
            ->setPaper('A4', 'landscape')
            ->set_option('defaultFont', 'DejaVu Sans')
            ->set_option('isFontSubsettingEnabled', true);
            
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/safe_prod.pdf', $pdf->output());
        $elapsed = round(microtime(true) - $t0, 2);
        $size = round(filesize('/home/highest-ye/htdocs/highest-ye.store/public/safe_prod.pdf') / 1024, 2);
        echo "SUCCESS! Safe Arabic DomPDF Products (471 items): {$elapsed}s, size: {$size} KB\\n";
    }
};

$controller->runTest();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_safe_shaper.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_safe_shaper.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_safe_shaper.php")
client.close()
