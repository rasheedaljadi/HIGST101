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
    public function testRowGrowth() {
        $req = new \\Illuminate\\Http\\Request(['format' => 'pdf']);
        $records = $this->getProcessedCustomers($req);
        
        $baseHeader = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"/><style>
            body { font-family: dejavusans; font-size: 7pt; direction: rtl; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 2px; text-align: right; }
        </style></head><body>
        <table><thead><tr><th>ID</th><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>التصنيف</th><th>الحالة</th><th>الطلبات</th><th>المبيعات</th><th>الصافي</th><th>الربح</th><th>الهامش%</th><th>متوسط الطلب</th></tr></thead>
        <tbody>';
        
        $rows = '';
        foreach ($records as $idx => $c) {
            $rows .= '<tr>';
            $rows .= '<td>#' . $c->customer_id . '</td>';
            $rows .= '<td>' . htmlspecialchars($c->name) . '</td>';
            $rows .= '<td>' . htmlspecialchars($c->email) . '</td>';
            $rows .= '<td>' . htmlspecialchars($c->phone) . '</td>';
            $rows .= '<td>' . htmlspecialchars($c->segment_label) . '</td>';
            $rows .= '<td>' . htmlspecialchars($c->status_label) . '</td>';
            $rows .= '<td>' . $c->total_orders . '</td>';
            $rows .= '<td>$' . number_format($c->gross_sales, 2) . '</td>';
            $rows .= '<td>$' . number_format($c->net_sales, 2) . '</td>';
            $rows .= '<td>$' . number_format($c->total_profit, 2) . '</td>';
            $rows .= '<td>' . $c->profit_margin . '%</td>';
            $rows .= '<td>$' . number_format($c->avg_order_value, 2) . '</td>';
            $rows .= '</tr>';
            
            $html = $baseHeader . $rows . '</tbody></table></body></html>';
            $mpdf = new \\Mpdf\\Mpdf(['mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L']);
            $mpdf->WriteHTML($html);
            $count = $idx + 1;
            echo "{$count} customers in table => {$mpdf->page} pages\\n";
        }
    }
};

$controller->testRowGrowth();
"""

sftp = client.open_sftp()
with sftp.file(f"{APP_DIR}/test_growth.php", 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php test_growth.php")
print(stdout.read().decode('utf-8', errors='replace'))
print(stderr.read().decode('utf-8', errors='replace'))

client.exec_command(f"rm {APP_DIR}/test_growth.php")
client.close()
