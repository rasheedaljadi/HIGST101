import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$totalIndices = DB::table('product_inventory_indices')->count();
$zeroIndices = DB::table('product_inventory_indices')->where('qty', 0)->count();
$positiveIndices = DB::table('product_inventory_indices')->where('qty', '>', 0)->count();

echo "TOTAL_INVENTORY_INDICES: $totalIndices" . PHP_EOL;
echo "ZERO_QTY_INDICES: $zeroIndices" . PHP_EOL;
echo "POSITIVE_QTY_INDICES: $positiveIndices" . PHP_EOL;

$totalInventories = DB::table('product_inventories')->count();
$positiveInventories = DB::table('product_inventories')->where('qty', '>', 0)->count();
$invBySource = DB::table('product_inventories')
    ->select('inventory_source_id', DB::raw('count(*) as count'), DB::raw('sum(qty) as total_qty'))
    ->groupBy('inventory_source_id')
    ->get();

echo "TOTAL_PRODUCT_INVENTORIES_ROWS: $totalInventories" . PHP_EOL;
echo "POSITIVE_PRODUCT_INVENTORIES_ROWS: $positiveInventories" . PHP_EOL;
echo "INVENTORIES_BY_SOURCE: " . json_encode($invBySource->toArray()) . PHP_EOL;
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/check_all_stock.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php check_all_stock.php && rm check_all_stock.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
