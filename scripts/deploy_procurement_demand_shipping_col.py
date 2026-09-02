import remote_ssh_helper as r
import os
import glob

client = r.get_ssh_client()
sftp = client.open_sftp()

# 1. Upload ProcurementDemandDataGrid.php
local_dg = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\DataGrids\ProcurementDemandDataGrid.php"
remote_dg = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"
print(f"Uploading DataGrid -> {remote_dg}")
sftp.put(local_dg, remote_dg)

# 2. Upload all 21 translation files
lang_base_local = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"
lang_base_remote = "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Resources/lang"

for loc in os.listdir(lang_base_local):
    loc_file_local = os.path.join(lang_base_local, loc, "app.php")
    if os.path.exists(loc_file_local):
        remote_loc_dir = f"{lang_base_remote}/{loc}"
        remote_loc_file = f"{remote_loc_dir}/app.php"
        try:
            sftp.stat(remote_loc_dir)
        except:
            sftp.mkdir(remote_loc_dir)
        sftp.put(loc_file_local, remote_loc_file)
        print(f"Uploaded lang/{loc}/app.php")

sftp.close()

# 3. Clear cache on server
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache clear: CODE {code} | {out}")

# 4. Test Datagrid response on server
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
echo "Columns Count: " . count($data['columns'] ?? []) . "\\n";
echo "Columns: " . implode(', ', array_column($data['columns'] ?? [], 'label')) . "\\n";

echo "\\nFirst 3 Records:\\n";
foreach (array_slice($data['records'] ?? [], 0, 3) as $r) {
    echo "  - Demand ID: {$r['demand_id']} | System Cost: {$r['system_cost']} | Cost With Shipping: " . strip_tags($r['cost_with_shipping'] ?? '') . " | Selling Price: " . strip_tags($r['customer_selling_price'] ?? '') . "\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_datagrid.php", "w") as f:
    f.write(php_test)
sftp2.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_demand_datagrid.php && rm test_demand_datagrid.php")
print(f"\nDatagrid Test Output:\n{out}")

client.close()
