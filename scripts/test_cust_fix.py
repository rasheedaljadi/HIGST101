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
    public function testCustFix() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/>
        <style>
            @page {
                size: A4 landscape;
                margin: 8mm 8mm;
            }
            body {
                font-family: dejavusans, sans-serif;
                font-size: 7.5pt;
                direction: rtl;
                color: #0f172a;
            }
            .header-table {
                width: 100%;
                border-bottom: 2px solid #0f172a;
                margin-bottom: 8px;
                border-collapse: collapse;
            }
            .header-title { font-size: 13pt; font-weight: bold; color: #0f172a; }
            .header-subtitle { font-size: 7.5pt; color: #475569; }
            .data-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 7pt;
            }
            .data-table th {
                background-color: #f1f5f9;
                color: #0f172a;
                font-weight: bold;
                border: 1px solid #94a3b8;
                padding: 4px 3px;
                text-align: right;
            }
            .data-table td {
                border: 1px solid #cbd5e1;
                padding: 3px 3px;
                text-align: right;
                color: #1e293b;
            }
            .data-table tr:nth-child(even) { background-color: #f8fafc; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
        </style>
        </head><body>';
        
        $html .= '<table class="header-table"><tr><td style="border:none; text-align:right;"><h1 class="header-title">👥 تقرير العملاء التفصيلي — هايست</h1><p class="header-subtitle">تاريخ ووقت استخراج التقرير: ' . now()->format('Y-m-d H:i:s') . '</p></td></tr></table>';
        
        $html .= '<table class="data-table"><thead><tr>';
        $html .= '<th style="width:5%;" class="text-center">ID</th>';
        $html .= '<th style="width:13%;">العميل</th>';
        $html .= '<th style="width:17%;">البريد</th>';
        $html .= '<th style="width:9%;">الهاتف</th>';
        $html .= '<th style="width:8%;" class="text-center">التصنيف</th>';
        $html .= '<th style="width:6%;" class="text-center">الحالة</th>';
        $html .= '<th style="width:6%;" class="text-center">الطلبات</th>';
        $html .= '<th style="width:8%;" class="text-right">المبيعات</th>';
        $html .= '<th style="width:8%;" class="text-right">الصافي</th>';
        $html .= '<th style="width:7%;" class="text-right">الربح</th>';
        $html .= '<th style="width:6%;" class="text-center">الهامش%</th>';
        $html .= '<th style="width:7%;" class="text-right">متوسط الطلب</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($records as $c) {
            $html .= '<tr>';
            $html .= '<td class="text-center font-bold">#' . $c->customer_id . '</td>';
            $html .= '<td class="font-bold">' . htmlspecialchars($c->name) . '</td>';
            $html .= '<td style="font-size:6.5pt;">' . htmlspecialchars($c->email) . '</td>';
            $html .= '<td>' . htmlspecialchars($c->phone) . '</td>';
            $html .= '<td class="text-center">' . htmlspecialchars($c->segment_label) . '</td>';
            $html .= '<td class="text-center">' . htmlspecialchars($c->status_label) . '</td>';
            $html .= '<td class="text-center font-bold">' . $c->total_orders . ' (' . $c->completed_orders . ')</td>';
            $html .= '<td class="text-right">$' . number_format($c->gross_sales, 2) . '</td>';
            $html .= '<td class="text-right font-bold">$' . number_format($c->net_sales, 2) . '</td>';
            $html .= '<td class="text-right font-bold">$' . number_format($c->total_profit, 2) . '</td>';
            $html .= '<td class="text-center font-bold">' . $c->profit_margin . '%</td>';
            $html .= '<td class="text-right">$' . number_format($c->avg_order_value, 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';
        
        $mpdf = new \\Mpdf\\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_top' => 6,
            'margin_bottom' => 6,
            'autoPageBreak' => true,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);
        
        echo "Fixed Customers PDF Page Count: " . $mpdf->page . "\\n";
        file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/test_fixed_cust.pdf', $mpdf->Output('', 'S'));
        echo "Saved to /public/test_fixed_cust.pdf (" . filesize('/home/highest-ye/htdocs/highest-ye.store/public/test_fixed_cust.pdf') . " bytes)\\n";
    }
};

$controller->testCustFix();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_cust_fix.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_cust_fix.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_cust_fix.php")
client.close()
