import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/DataGrids/ExternalPlatformOrderDataGrid.php", "packages/Webkul/Procurement/src/DataGrids/ExternalPlatformOrderDataGrid.php"),
]

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

for rel_local, rel_remote in files_to_sync:
    local_path = os.path.join(local_base, rel_local)
    remote_path = f"{remote_base}/{rel_remote}"
    print(f"Uploading {rel_local} -> {remote_path}")
    sftp.put(local_path, remote_path)

# Upload all 21 translation files
lang_base_local = os.path.join(local_base, "packages/Webkul/Procurement/src/Resources/lang")
lang_base_remote = f"{remote_base}/packages/Webkul/Procurement/src/Resources/lang"

for loc in os.listdir(lang_base_local):
    loc_file_local = os.path.join(lang_base_local, loc, "app.php")
    if os.path.exists(loc_file_local):
        remote_loc_file = f"{lang_base_remote}/{loc}/app.php"
        sftp.put(loc_file_local, remote_loc_file)

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test verification on server
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ExternalPlatformOrderController;
use Webkul\\Procurement\\DataGrids\\ExternalPlatformOrderDataGrid;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\ViewErrorBag;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. TESTING PLATFORM ORDERS CONTROLLER & VIEW RENDER\\n";
echo "=========================================================\\n";
$controller = app(ExternalPlatformOrderController::class);
$req = Request::create('/admin/dropshipping/procurement-v2/platform-orders', 'GET');
$resp = $controller->index($req);

$html = $resp->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";

echo "\\n=========================================================\\n";
echo "2. TESTING DATAGRID JSON WITH NEW PRICE & SHIPPING COLUMNS\\n";
echo "=========================================================\\n";
$dg = app(ExternalPlatformOrderDataGrid::class);
$json = $dg->toJson();
$data = $json->getData(true);

echo "Columns Count: " . count($data['columns'] ?? []) . "\\n";
foreach ($data['columns'] as $col) {
    echo "  - Column: {$col['index']} | Label: {$col['label']}\\n";
}

echo "\\nRows Count: " . count($data['records'] ?? []) . "\\n";
foreach (array_slice($data['records'] ?? [], 0, 3) as $row) {
    echo "\\nRow ID: {$row['platform_order_id']} | Ext Order: {$row['external_order_id']} | PO: {$row['purchase_order_number']}\\n";
    echo "  - Items Cost: " . ($row['spo_items_total'] ?? 'NULL') . "\\n";
    echo "  - Shipping Fee: " . ($row['spo_shipping_total'] ?? 'NULL') . "\\n";
    echo "  - Grand Total: " . ($row['spo_expected_total'] ?? 'NULL') . "\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_platform_orders_columns.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_platform_orders_columns.php && rm test_platform_orders_columns.php")
print(f"\nVerification Output:\n{out}")

client.close()
