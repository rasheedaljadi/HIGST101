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
use Illuminate\\Support\\Facades\\Storage;

$files = glob(public_path('themes/*/default/build/assets/logo-*.png'));
$logoPath = !empty($files) ? $files[0] : null;
$logoBase64 = $logoPath && file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

echo "Logo Path: " . $logoPath . "\\n";
echo "Logo Base64 length: " . (strlen($logoBase64 ?? '')) . "\\n";

$arabic = new Arabic();
$html = '<!DOCTYPE html>
<html lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; direction: rtl; text-align: right; }
        .header { width: 100%; margin-bottom: 20px; }
        .header td { border: none; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="text-align: right;">
                <h2>' . $arabic->utf8Glyphs("تقرير المنتجات التجريبي — هايست") . '</h2>
            </td>
            <td style="text-align: left; width: 150px;">
                <img src="' . $logoBase64 . '" style="max-height: 40px; width: auto;" alt="Logo"/>
            </td>
        </tr>
    </table>
</body>
</html>';

$pdf = DomPdf::loadHTML($html)
    ->setPaper('A4', 'landscape')
    ->set_option('defaultFont', 'DejaVu Sans')
    ->set_option('isFontSubsettingEnabled', true)
    ->set_option('isRemoteEnabled', true);

file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_logo_pdf.pdf', $pdf->output());
echo "Saved /public/test_logo_pdf.pdf (" . filesize('/home/highest-ye/htdocs/highest-ye.store/public/test_logo_pdf.pdf') . " bytes)\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_logo.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_logo.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_logo.php")
client.close()
