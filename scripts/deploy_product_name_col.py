import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php", "packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"),
]

# Sync all 21 locale files
lang_dir = r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\lang"
for loc in os.listdir(lang_dir):
    loc_file = os.path.join(lang_dir, loc, "app.php")
    if os.path.isfile(loc_file):
        rel = f"packages/Webkul/Procurement/src/Resources/lang/{loc}/app.php"
        files_to_sync.append((rel, rel))

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = os.path.join(local_base, rel_local)
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Render Demands DataGrid with product name & variant badges
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\DataGrids\\ProcurementDemandDataGrid;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$grid = app(ProcurementDemandDataGrid::class);
$grid->prepareColumns();

echo "=========================================================\\n";
echo "1. VERIFYING PRODUCT NAME COLUMN IN DATAGRID\\n";
echo "=========================================================\\n";
$col = collect($grid->getColumns())->firstWhere('index', 'product_name');
if ($col) {
    echo "Found Column: '{$col->getIndex()}' (Label: '{$col->getLabel()}') ✅\\n";
} else {
    echo "Column 'product_name' NOT found ❌\\n";
}

echo "\\n=========================================================\\n";
echo "2. TESTING ROW RENDERING FOR LATEST DEMANDS\\n";
echo "=========================================================\\n";
$query = $grid->prepareQueryBuilder();
$rows = $query->limit(3)->get();
foreach ($rows as $row) {
    echo "\\nDemand ID #{$row->demand_id} (Order #{$row->order_increment_id}):\\n";
    $closure = $col->getClosure();
    $renderedHtml = $closure($row);
    echo "Rendered Product HTML:\\n" . $renderedHtml . "\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_product_name_col.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_product_name_col.php && rm test_product_name_col.php")
print(f"\nVerification Output:\n{out}")

client.close()
