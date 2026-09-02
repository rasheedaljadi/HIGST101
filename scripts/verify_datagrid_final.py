import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_dg = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\DataGrids\ProcurementDemandDataGrid.php"
remote_dg = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"
print(f"Uploading DataGrid -> {remote_dg}")
sftp.put(local_dg, remote_dg)
sftp.close()

# Clear views and app cache
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan view:clear && php8.4 artisan cache:clear")

# Test Datagrid response on server
php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ProcurementDemandDataGrid;

$dg = app(ProcurementDemandDataGrid::class);
$json = $dg->toJson();
$data = $json->getData(true);

echo "Datagrid Total Records: " . ($data['total'] ?? count($data['records'] ?? [])) . "\\n";
echo "Columns: " . implode(', ', array_column($data['columns'] ?? [], 'label')) . "\\n";

echo "\\nFirst 4 Records:\\n";
foreach (array_slice($data['records'] ?? [], 0, 4) as $r) {
    echo "  - Demand ID: {$r['demand_id']}\\n";
    echo "    Customer Selling Price: " . strip_tags($r['customer_selling_price'] ?? '') . "\\n";
    echo "    System Cost: " . strip_tags($r['system_cost'] ?? '') . "\\n";
    echo "    Cost With Shipping: " . strip_tags($r['cost_with_shipping'] ?? '') . "\\n";
    echo "    AliExpress Cost: " . strip_tags($r['aliexpress_cost'] ?? '') . "\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_datagrid2.php", "w") as f:
    f.write(php_test)
sftp2.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_demand_datagrid2.php && rm test_demand_datagrid2.php")
print(f"\nDatagrid Test Output:\n{out}")

client.close()
