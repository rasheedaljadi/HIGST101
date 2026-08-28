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

$controller = new class(
    app()->make(\\Webkul\\Product\\Repositories\\ProductRepository::class),
    app()->make(\\Webkul\\Category\\Repositories\\CategoryRepository::class)
) extends \\Webkul\\Admin\\Http\\Controllers\\Reporting\\DetailedReportController {
    public function testHeaderPage() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>
            @page {
                size: A4 landscape;
                margin-top: 20mm;
                margin-bottom: 10mm;
                margin-left: 8mm;
                margin-right: 8mm;
                header: html_reportHeader;
                footer: html_reportFooter;
            }
            body { font-family: dejavusans; font-size: 7.5pt; direction: rtl; }
            table.data-table { width: 100%; border-collapse: collapse; font-size: 7pt; }
            table.data-table th, table.data-table td { border: 1px solid #94a3b8; padding: 3.5px 3px; text-align: right; }
            table.data-table th { background: #f1f5f9; color: #0f172a; font-weight: bold; }
            table.data-table tr:nth-child(even) { background: #f8fafc; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
        </style></head><body>
        
        <htmlpageheader name="reportHeader">
            <div style="border-bottom: 2px solid #0f172a; padding-bottom: 4px; direction: rtl;">
                <table style="width:100%; border:none;">
                    <tr>
                        <td style="border:none; text-align:right; font-size:12pt; font-weight:bold; color:#0f172a;">👥 تقرير العملاء التفصيلي والمالي — هايست</td>
                        <td style="border:none; text-align:left; font-size:7pt; color:#475569;">تاريخ التقرير: ' . now()->format('Y-m-d H:i') . '</td>
                    </tr>
                </table>
            </div>
        </htmlpageheader>
        
        <htmlpagefooter name="reportFooter">
            <div style="border-top: 1px solid #e2e8f0; font-size: 7pt; text-align: center; padding-top: 4px; color: #64748b;">
                صفحة {PAGENO} من {nbpg}
            </div>
        </htmlpagefooter>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;" class="text-center">ID</th>
                    <th style="width: 14%;">العميل</th>
                    <th style="width: 16%;">البريد</th>
                    <th style="width: 8%;">الهاتف</th>
                    <th style="width: 8%;" class="text-center">التصنيف</th>
                    <th style="width: 6%;" class="text-center">الحالة</th>
                    <th style="width: 6%;" class="text-center">الطلبات</th>
                    <th style="width: 7%;" class="text-right">المبيعات</th>
                    <th style="width: 7%;" class="text-right">الصافي</th>
                    <th style="width: 6%;" class="text-right">التكلفة</th>
                    <th style="width: 6%;" class="text-right">الربح</th>
                    <th style="width: 5%;" class="text-center">الهامش</th>
                    <th style="width: 7%;" class="text-right">متوسط الطلب</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($records as $c) {
                $html .= '<tr>';
                $html .= '<td class="text-center font-bold">#' . $c->customer_id . '</td>';
                $html .= '<td class="font-bold">' . htmlspecialchars($c->name) . '</td>';
                $html .= '<td style="font-size:6pt;">' . htmlspecialchars($c->email) . '</td>';
                $html .= '<td>' . htmlspecialchars($c->phone) . '</td>';
                $html .= '<td class="text-center">' . htmlspecialchars($c->segment_label) . '</td>';
                $html .= '<td class="text-center">' . htmlspecialchars($c->status_label) . '</td>';
                $html .= '<td class="text-center font-bold">' . $c->total_orders . ' (' . $c->completed_orders . ')</td>';
                $html .= '<td class="text-right">$' . number_format($c->gross_sales, 2) . '</td>';
                $html .= '<td class="text-right font-bold">$' . number_format($c->net_sales, 2) . '</td>';
                $html .= '<td class="text-right">$' . number_format($c->total_cost, 2) . '</td>';
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
                'autoPageBreak' => true,
            ]);
            $mpdf->SetDirectionality('rtl');
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->WriteHTML($html);
            
            echo "CUSTOMER REPORT with htmlpageheader: " . $mpdf->page . " pages\\n";
            file_put_contents('/home/highest-ye/htdocs/highest-ye.store/public/perfect_cust.pdf', $mpdf->Output('', 'S'));
            echo "Saved to /public/perfect_cust.pdf\\n";
    }
};

$controller->testHeaderPage();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_htmlpageheader.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_htmlpageheader.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_htmlpageheader.php")
client.close()
