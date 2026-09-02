import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php", "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php"),
    ("packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php", "packages/Webkul/Procurement/src/DataGrids/ProcurementDemandDataGrid.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php"),
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
        print(f"Uploaded lang/{loc}/app.php")

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

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementDemandController;
use Webkul\\Procurement\\DataGrids\\ProcurementDemandDataGrid;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\ViewErrorBag;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. TESTING DEMANDS CONTROLLER INDEX & VIEW RENDER\\n";
echo "=========================================================\\n";
$controller = app(ProcurementDemandController::class);
$req = Request::create('/admin/dropshipping/procurement-v2/demands', 'GET');
$resp = $controller->index($req);

$html = $resp->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";
echo "Contains tab 'الكل': " . (str_contains($html, 'الكل') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains tab 'متاح للتجميع': " . (str_contains($html, 'متاح للتجميع') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains tab 'تم التجميع': " . (str_contains($html, 'تم التجميع') ? "YES ✅" : "NO ❌") . "\\n";

echo "\\n=========================================================\\n";
echo "2. TESTING DATAGRID FILTERING PER TAB\\n";
echo "=========================================================\\n";

foreach (['all', 'open_for_batching', 'batched'] as $tabState) {
    request()->merge(['state' => $tabState]);
    $dg = app(ProcurementDemandDataGrid::class);
    $json = $dg->toJson();
    $data = $json->getData(true);
    $total = $data['total'] ?? count($data['records'] ?? []);
    echo "Tab '{$tabState}': Total Records returned = {$total}\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_demand_tabs.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_demand_tabs.php && rm test_demand_tabs.php")
print(f"\nVerification Output:\n{out}")

client.close()
