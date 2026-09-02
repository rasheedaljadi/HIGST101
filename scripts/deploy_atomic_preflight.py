import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Services/ProcurementBatchService.php", "packages/Webkul/Procurement/src/Services/ProcurementBatchService.php"),
    ("packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php", "packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"),
    ("packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php", "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php"),
    ("packages/Webkul/Procurement/src/Routes/admin-routes.php", "packages/Webkul/Procurement/src/Routes/admin-routes.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php"),
]

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

# Verification on server: Check route resolution and batch view render
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementBatchController;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\ViewErrorBag;

echo "=========================================================\\n";
echo "1. VERIFYING ROUTE RESOLUTION FOR REMOVE_SUPPLIER_ORDER\\n";
echo "=========================================================\\n";
$route = route('admin.procurement.batches.remove_supplier_order', ['batch' => 84, 'spo' => 105]);
echo "Generated Route URL: {$route}\\n";

echo "\\n=========================================================\\n";
echo "2. VERIFYING BATCH #84 VIEW RENDERING WITH REMOVE BUTTONS\\n";
echo "=========================================================\\n";
view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$controller = app(ProcurementBatchController::class);
$req = Request::create('/admin/dropshipping/procurement-v2/batches/view/84', 'GET');
$resp = $controller->view(84);
$html = $resp->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";

if (strpos($html, 'إزالة من الدفعة') !== false) {
    echo "Remove from Batch Button Found in HTML! ✅\\n";
} else {
    echo "Remove from Batch Button NOT Found in HTML ❌\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_atomic_preflight_deploy.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_atomic_preflight_deploy.php && rm test_atomic_preflight_deploy.php")
print(f"\nVerification Output:\n{out}")

client.close()
