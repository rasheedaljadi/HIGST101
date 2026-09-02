import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php", "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php"),
    ("packages/Webkul/Procurement/src/Routes/admin-routes.php", "packages/Webkul/Procurement/src/Routes/admin-routes.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php"),
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

# Clear cache and route cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan route:clear && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Render Demands view and verify Sync Stock button & route
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementDemandController;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\ViewErrorBag;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. VERIFYING ROUTE FOR DEMANDS SYNC STOCK\\n";
echo "=========================================================\\n";
$routeUrl = route('admin.procurement.demands.sync_stock');
echo "Generated Route URL: {$routeUrl} ✅\\n";

echo "\\n=========================================================\\n";
echo "2. VERIFYING SYNC STOCK BUTTON IN DEMANDS VIEW\\n";
echo "=========================================================\\n";
$controller = app(ProcurementDemandController::class);
$req = Request::create('/admin/dropshipping/procurement-v2/demands', 'GET');
$resp = $controller->index($req);
$html = $resp->render();

if (strpos($html, 'مزامنة المخزون') !== false) {
    echo "Button 'مزامنة المخزون' Found in Demands View HTML! ✅\\n";
} else {
    echo "Button 'مزامنة المخزون' NOT Found in HTML ❌\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_sync_stock_btn.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_sync_stock_btn.php && rm test_sync_stock_btn.php")
print(f"\nVerification Output:\n{out}")

client.close()
