import sys
import paramiko
import json

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)
sftp = client.open_sftp()

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$demand = DB::table('procurement_demands')
    ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
    ->where('orders.increment_id', '313')
    ->orWhere('procurement_demands.order_id', 313)
    ->orWhere('procurement_demands.id', 313)
    ->select('procurement_demands.*', 'orders.increment_id as order_increment_id')
    ->first();

echo "=== PROCUREMENT DEMAND ===\\n";
echo json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";

if ($demand) {
    echo "\\n=== EXCEPTIONS FOR DEMAND / ORDER ===\\n";
    $exceptions = DB::table('procurement_exceptions')
        ->where('demand_id', $demand->id)
        ->orWhere('order_id', $demand->order_id)
        ->get();
    echo json_encode($exceptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";

    echo "\\n=== ORDER ITEM ===\\n";
    $item = DB::table('order_items')->where('id', $demand->order_item_id)->first();
    echo json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
}
"""

with sftp.open(f"{APP_DIR}/inspect_demand_313_clean.php", 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command(f"cd {APP_DIR} && php inspect_demand_313_clean.php && rm inspect_demand_313_clean.php")
print(stdout.read().decode('utf-8', errors='replace'))
client.close()
