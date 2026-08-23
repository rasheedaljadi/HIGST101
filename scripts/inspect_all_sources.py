import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventorySource;

$sources = InventorySource::all();
foreach ($sources as $s) {
    $count = DB::table('product_inventories')->where('inventory_source_id', $s->id)->count();
    $qtySum = DB::table('product_inventories')->where('inventory_source_id', $s->id)->sum('qty');
    echo "SOURCE_ID: {$s->id} | CODE: {$s->code} | NAME: {$s->name} | ROWS: {$count} | TOTAL_QTY: {$qtySum}" . PHP_EOL;
}

$nonAeProducts = DB::table('products')->where('sku', 'not like', 'ae-%')->count();
echo "NON_AE_PRODUCTS_COUNT: $nonAeProducts" . PHP_EOL;
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/inspect_all_sources.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php inspect_all_sources.php && rm inspect_all_sources.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
