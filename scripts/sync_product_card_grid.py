import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

sftp = client.open_sftp()

files = [
    ('e:\\HIGESTO NEW1\\higest\higest101\\packages\\Webkul\\Inventory\\src\\DataGrids\\InventoryProductCardDataGrid.php', '/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Inventory/src/DataGrids/InventoryProductCardDataGrid.php'),
    ('e:\\HIGESTO NEW1\\higest\\higest101\\packages\\Webkul\\Inventory\\src\\Http\\Controllers\\Admin\\InventoryProductCardController.php', '/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Inventory/src/Http/Controllers/Admin/InventoryProductCardController.php'),
]

for local, remote in files:
    print(f"Uploading {local} -> {remote}")
    sftp.put(local, remote)

test_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Webkul\Inventory\DataGrids\InventoryProductCardDataGrid;

$grid = app(InventoryProductCardDataGrid::class);
$query = $grid->prepareQueryBuilder();
$results = $query->take(10)->get();

echo "TOTAL_RETURNED_ROWS: " . count($results) . PHP_EOL;
foreach ($results as $row) {
    echo "ID: {$row->product_id} | SKU: {$row->sku} | TYPE: {$row->product_type} | VIRTUAL_PROJECTION: {$row->virtual_projection_qty} | SALABLE_YE: {$row->salable_ye_qty}" . PHP_EOL;
}
"""

with sftp.file('/home/highest-ye/htdocs/highest-ye.store/test_card_grid.php', 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php test_card_grid.php && rm test_card_grid.php && php artisan optimize:clear')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
