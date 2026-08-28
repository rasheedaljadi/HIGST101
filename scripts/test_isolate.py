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
    public function runTests() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $build = function($records, $useEmailCell, $useDash, $useCompletedOrders, $useCustomHeader, $extraCols) {
            $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>
                body { font-family: dejavusans; font-size: 7pt; direction: rtl; }
                table { width: 100%; border-collapse: collapse; font-size: 7pt; }
                th, td { border: 1px solid #ccc; padding: 2px; text-align: right; }
                .email-cell { font-size: 6pt; direction: ltr; text-align: right; }
            </style></head><body>';
            
            if ($useCustomHeader) {
                $html .= '<table style="width:100%; border-bottom: 2px solid #000; margin-bottom: 5px;"><tr><td><h2>تقرير العملاء</h2></td></tr></table>';
            }
            
            $html .= '<table><thead><tr><th>ID</th><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>التصنيف</th><th>الحالة</th><th>الطلبات</th><th>المبيعات</th><th>الصافي</th><th>الربح</th><th>الهامش%</th><th>متوسط الطلب</th>' . ($extraCols ? '<th>تاريخ التسجيل</th><th>آخر طلب</th>' : '') . '</tr></thead><tbody>';
            
            foreach ($records as $c) {
                $html .= '<tr>';
                $html .= '<td>#' . $c->customer_id . '</td>';
                $html .= '<td>' . htmlspecialchars($c->name) . '</td>';
                if ($useEmailCell) {
                    $html .= '<td class="email-cell">' . htmlspecialchars($c->email) . '</td>';
                } else {
                    $html .= '<td>' . htmlspecialchars($c->email) . '</td>';
                }
                $html .= '<td>' . ($useDash ? '—' : htmlspecialchars($c->phone)) . '</td>';
                $html .= '<td>' . htmlspecialchars($c->segment_label) . '</td>';
                $html .= '<td>' . htmlspecialchars($c->status_label) . '</td>';
                if ($useCompletedOrders) {
                    $html .= '<td>' . $c->total_orders . ' (' . $c->completed_orders . ')</td>';
                } else {
                    $html .= '<td>' . $c->total_orders . '</td>';
                }
                $html .= '<td>$' . number_format($c->gross_sales, 2) . '</td>';
                $html .= '<td>$' . number_format($c->net_sales, 2) . '</td>';
                $html .= '<td>$' . number_format($c->total_profit, 2) . '</td>';
                $html .= '<td>' . $c->profit_margin . '%</td>';
                $html .= '<td>$' . number_format($c->avg_order_value, 2) . '</td>';
                if ($extraCols) {
                    $html .= '<td>' . $c->created_at . '</td><td>' . $c->last_order_date . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></body></html>';
            return $html;
        };
        
        $tests = [
            'Base (12 cols)' => $build($records, false, false, false, false, false),
            'With Custom Header' => $build($records, false, false, false, true, false),
            'With Email Cell class' => $build($records, true, false, false, false, false),
            'With Dash' => $build($records, false, true, false, false, false),
            'With Completed Orders' => $build($records, false, false, true, false, false),
            'With 14 cols (created_at + last_order_date)' => $build($records, false, false, false, false, true),
        ];

        foreach ($tests as $name => $h) {
            $m = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
            $m->SetDirectionality('rtl');
            $m->autoScriptToLang = true;
            $m->autoLangToFont = true;
            $m->WriteHTML($h);
            echo "$name => {$m->page} pages\\n";
        }
    }
};

$controller->runTests();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_isolate.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_isolate.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_isolate.php")
client.close()
