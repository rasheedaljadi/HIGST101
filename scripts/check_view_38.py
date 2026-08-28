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

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementDemand;

$demand = ProcurementDemand::with(['order', 'orderItem'])->find(38);
if ($demand) {
    $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
    $deficit = $demand->remaining_unbatched_qty;
    $lineCost = $deficit * $unitCost;

    echo "=== CURRENT VIEW DATA FOR DEMAND #38 (Order #315) ===\n";
    echo "Demand ID: #" . $demand->id . "\n";
    echo "Order: #" . ($demand->order?->increment_id ?: $demand->order_id) . "\n";
    echo "Supplier Store: " . ($demand->supplier_store_name ?: $demand->supplier_store_id) . "\n";
    echo "Supplier SKU: " . $demand->supplier_sku_id . "\n";
    echo "Deficit Qty: " . $deficit . "\n";
    echo "Unit Cost: $" . number_format($unitCost, 2) . "\n";
    echo "Total Line Cost: $" . number_format($lineCost, 2) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file(f'{APP_DIR}/check_view_38_tmp.php', 'w') as f:
    f.write(php_code)
sftp.close()

stdin, stdout, stderr = client.exec_command(f'cd {APP_DIR} && php check_view_38_tmp.php && rm -f check_view_38_tmp.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
