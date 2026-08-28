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
    public function testPdfClean() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $catTree = $this->getCategoryHierarchy('ar');
        $records = $this->getProcessedProducts($req, 'ar', $catTree);
        
        // Clean CSS without conflicting page-break rules
        $cleanCss = '
            body { font-family: "DejaVu Sans", sans-serif; font-size: 7.5pt; direction: rtl; color: #111827; }
            .header-table { width: 100%; border-bottom: 2px solid #0f172a; margin-bottom: 8px; border-collapse: collapse; }
            .header-title { font-size: 13pt; font-weight: bold; color: #0f172a; margin: 0; }
            .header-subtitle { font-size: 7.5pt; color: #475569; margin: 0; }
            .data-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
            .data-table th { background-color: #f1f5f9; color: #0f172a; font-weight: bold; border: 1px solid #94a3b8; padding: 4px 3px; text-align: right; }
            .data-table td { border: 1px solid #cbd5e1; padding: 3px; text-align: right; color: #1e293b; }
            .data-table tr:nth-child(even) { background-color: #f8fafc; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
        ';
        
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>' . $cleanCss . '</style></head><body>';
        $html .= '<table class="header-table"><tr><td style="border:none; text-align:right;"><h1 class="header-title">📊 تقرير المنتجات التفصيلي — هايست</h1><p class="header-subtitle">تاريخ ووقت استخراج التقرير: ' . now()->format('Y-m-d H:i:s') . '</p></td></tr></table>';
        $html .= '<table class="data-table"><thead><tr>';
        $html .= '<th style="width:5%;" class="text-center">ID</th><th style="width:12%;">SKU</th><th style="width:23%;">اسم المنتج</th><th style="width:12%;">الفئة الرئيسية</th><th style="width:8%;" class="text-center">النوع</th><th style="width:8%;" class="text-center">المصدر</th><th style="width:10%;">المورد</th><th style="width:6%;" class="text-center">المتغيرات</th><th style="width:8%;" class="text-right">التكلفة</th><th style="width:8%;" class="text-right">سعر البيع</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($records->take(100) as $p) {
            $html .= '<tr>';
            $html .= '<td class="text-center font-bold">#' . $p->product_id . '</td>';
            $html .= '<td>' . $p->sku . '</td>';
            $html .= '<td>' . htmlspecialchars($p->name ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars($p->main_category ?? '') . '</td>';
            $html .= '<td class="text-center">' . ($p->type === 'configurable' ? 'بمتغيرات' : 'بسيط') . '</td>';
            $html .= '<td class="text-center">' . ($p->source === 'aliexpress' ? 'AliExpress' : 'داخلي') . '</td>';
            $html .= '<td>' . htmlspecialchars($p->supplier ?? '') . '</td>';
            $html .= '<td class="text-center">' . ($p->variants_count > 0 ? $p->variants_count : '—') . '</td>';
            $html .= '<td class="text-right">$' . number_format($p->cost_price, 2) . '</td>';
            $html .= '<td class="text-right font-bold">$' . number_format($p->selling_price, 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';
        
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'autoPageBreak' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        
        echo "Clean CSS Page count for 100 records: " . $mpdf->page . "\\n";
        
        // Full 471 records
        $htmlFull = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>' . $cleanCss . '</style></head><body>';
        $htmlFull .= '<table class="header-table"><tr><td style="border:none; text-align:right;"><h1 class="header-title">📊 تقرير المنتجات التفصيلي — هايست</h1><p class="header-subtitle">تاريخ ووقت استخراج التقرير: ' . now()->format('Y-m-d H:i:s') . '</p></td></tr></table>';
        $htmlFull .= '<table class="data-table"><thead><tr>';
        $htmlFull .= '<th style="width:5%;" class="text-center">ID</th><th style="width:12%;">SKU</th><th style="width:23%;">اسم المنتج</th><th style="width:12%;">الفئة الرئيسية</th><th style="width:8%;" class="text-center">النوع</th><th style="width:8%;" class="text-center">المصدر</th><th style="width:10%;">المورد</th><th style="width:6%;" class="text-center">المتغيرات</th><th style="width:8%;" class="text-right">التكلفة</th><th style="width:8%;" class="text-right">سعر البيع</th>';
        $htmlFull .= '</tr></thead><tbody>';
        
        foreach ($records as $p) {
            $htmlFull .= '<tr>';
            $htmlFull .= '<td class="text-center font-bold">#' . $p->product_id . '</td>';
            $html .= '<td>' . $p->sku . '</td>';
            $htmlFull .= '<td>' . htmlspecialchars($p->name ?? '') . '</td>';
            $htmlFull .= '<td>' . htmlspecialchars($p->main_category ?? '') . '</td>';
            $htmlFull .= '<td class="text-center">' . ($p->type === 'configurable' ? 'بمتغيرات' : 'بسيط') . '</td>';
            $htmlFull .= '<td class="text-center">' . ($p->source === 'aliexpress' ? 'AliExpress' : 'داخلي') . '</td>';
            $htmlFull .= '<td>' . htmlspecialchars($p->supplier ?? '') . '</td>';
            $htmlFull .= '<td class="text-center">' . ($p->variants_count > 0 ? $p->variants_count : '—') . '</td>';
            $htmlFull .= '<td class="text-right">$' . number_format($p->cost_price, 2) . '</td>';
            $htmlFull .= '<td class="text-right font-bold">$' . number_format($p->selling_price, 2) . '</td>';
            $htmlFull .= '</tr>';
        }
        $htmlFull .= '</tbody></table></body></html>';
        
        $mpdf2 = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5,
            'autoPageBreak' => true,
        ]);
        $mpdf2->SetDirectionality('rtl');
        $mpdf2->autoScriptToLang = true;
        $mpdf2->autoLangToFont = true;
        $mpdf2->WriteHTML($htmlFull);
        
        echo "Clean CSS Page count for ALL 471 records: " . $mpdf2->page . "\\n";
    }
};

$controller->testPdfClean();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_clean_pdf.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_clean_pdf.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_clean_pdf.php")
client.close()
