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

$files = glob(public_path('themes/*/default/build/assets/logo-*.png'));
$logoPath = !empty($files) ? $files[0] : null;
$logoBase64 = $logoPath && file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

$arabic = new Arabic();

$html = '<!DOCTYPE html>
<html lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; margin: 5mm; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; font-size: 7.5pt; text-align: right; }
        th { background: #f8fafc; font-weight: bold; }
        .text-center { text-align: center; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: none; }
        .header-table td { border: none; padding: 0; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="text-align: left; width: 140px; vertical-align: middle;">
                <img src="' . $logoBase64 . '" style="height: 32px; width: auto;" alt="Logo"/>
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <h1 style="font-size: 13pt; margin: 0; color: #0f172a;">' . $arabic->utf8Glyphs("تقرير المنتجات التفصيلي — هايست") . '</h1>
                <p style="font-size: 7.5pt; margin: 3px 0 0; color: #64748b;">' . $arabic->utf8Glyphs("تاريخ ووقت استخراج التقرير: 2026-08-27 22:15:00") . '</p>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">' . $arabic->utf8Glyphs("الحالة") . '</th>
                <th style="width: 7%;" class="text-center">' . $arabic->utf8Glyphs("سعر البيع") . '</th>
                <th style="width: 7%;" class="text-center">' . $arabic->utf8Glyphs("التكلفة") . '</th>
                <th style="width: 5%;" class="text-center">' . $arabic->utf8Glyphs("المخزون") . '</th>
                <th style="width: 5%;" class="text-center">' . $arabic->utf8Glyphs("المتغيرات") . '</th>
                <th style="width: 10%;">' . $arabic->utf8Glyphs("المورد") . '</th>
                <th style="width: 7%;" class="text-center">' . $arabic->utf8Glyphs("المصدر") . '</th>
                <th style="width: 7%;" class="text-center">' . $arabic->utf8Glyphs("النوع") . '</th>
                <th style="width: 12%;">' . $arabic->utf8Glyphs("الفئة الرئيسية") . '</th>
                <th style="width: 22%;">' . $arabic->utf8Glyphs("اسم المنتج") . '</th>
                <th style="width: 14%;" class="text-center">SKU</th>
                <th style="width: 4%;" class="text-center">ID</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">' . $arabic->utf8Glyphs("نشط") . '</td>
                <td class="text-center">$6.41</td>
                <td class="text-center">$5.83</td>
                <td class="text-center">3996</td>
                <td class="text-center">4</td>
                <td>Battery Store</td>
                <td class="text-center">AliExpress</td>
                <td class="text-center">' . $arabic->utf8Glyphs("بمتغيرات") . '</td>
                <td>' . $arabic->utf8Glyphs("الأدوات") . '</td>
                <td>' . $arabic->utf8Glyphs("مصباح عمل LED لـ Makita و Dewalt أدوات كهربائية") . '</td>
                <td class="text-center">ae-1005012949877534</td>
                <td class="text-center">#8530</td>
            </tr>
        </tbody>
    </table>
</body>
</html>';

$pdf = DomPdf::loadHTML($html)
    ->setPaper('A4', 'landscape')
    ->set_option('defaultFont', 'DejaVu Sans')
    ->set_option('isFontSubsettingEnabled', true);

file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_flipped_columns.pdf', $pdf->output());
echo "SUCCESS! Created /public/test_flipped_columns.pdf\\n";
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_flipped.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_flipped.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_flipped.php")
client.close()
