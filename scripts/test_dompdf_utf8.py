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

$arabic = new Arabic();

$testText = "تقرير المنتجات التفصيلي — هايست (تجربة الخط العربي الكامل)";
$shapedText = $arabic->utf8Glyphs($testText);

$html = '<!DOCTYPE html>
<html lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            text-align: right;
            direction: rtl;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 4px; text-align: right; }
    </style>
</head>
<body>
    <h1>' . $shapedText . '</h1>
    <table>
        <tr>
            <th>' . $arabic->utf8Glyphs("المعرف") . '</th>
            <th>' . $arabic->utf8Glyphs("اسم المنتج") . '</th>
            <th>' . $arabic->utf8Glyphs("الحالة") . '</th>
            <th>' . $arabic->utf8Glyphs("السعر") . '</th>
        </tr>
        <tr>
            <td>#101</td>
            <td>' . $arabic->utf8Glyphs("مصباح ليد ذكي قابل للشحن") . '</td>
            <td>' . $arabic->utf8Glyphs("متوفر بالمخزون") . '</td>
            <td>$25.50</td>
        </tr>
    </table>
</body>
</html>';

$pdf = DomPdf::loadHTML($html)
    ->setPaper('A4', 'landscape')
    ->set_option('defaultFont', 'DejaVu Sans')
    ->set_option('isFontSubsettingEnabled', true);

file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_arabic_dompdf.pdf', $pdf->output());
echo "Saved /public/test_arabic_dompdf.pdf\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_dompdf_utf8.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_dompdf_utf8.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_dompdf_utf8.php")
client.close()
