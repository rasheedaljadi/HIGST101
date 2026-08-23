import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

sftp = client.open_sftp()
local_file = 'e:\\HIGESTO NEW1\\higest\\higest101\\packages\\Webkul\\Inventory\\src\\Http\\Controllers\\Admin\\InventoryProductCardController.php'
remote_file = '/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Inventory/src/Http/Controllers/Admin/InventoryProductCardController.php'

print(f"Uploading {local_file} -> {remote_file}")
sftp.put(local_file, remote_file)

test_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Webkul\Inventory\Http\Controllers\Admin\InventoryProductCardController;

$controller = app(InventoryProductCardController::class);
$view = $controller->show(2626);
$data = $view->getData();

echo "PRODUCT_ID: " . $data['product']->id . " | SKU: " . $data['product']->sku . PHP_EOL;
echo "VIRTUAL_PROJECTION_QTY: " . ($data['virtualProjection']->current_qty ?? 0) . PHP_EOL;
echo "TOTAL_SALABLE_LOCAL: " . $data['totalSalableLocal'] . PHP_EOL;
foreach ($data['localSources'] as $src) {
    echo "  SRC: {$src->code} | IS_SALABLE: {$src->is_salable} | IS_DELIVERY: {$src->is_delivery_source} | QTY: {$src->current_qty}" . PHP_EOL;
}
"""

with sftp.file('/home/highest-ye/htdocs/highest-ye.store/test_product_view.php', 'w') as f:
    f.write(test_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php test_product_view.php && rm test_product_view.php && php artisan optimize:clear')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
