import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Models/ProcurementBatch.php", "packages/Webkul/Procurement/src/Models/ProcurementBatch.php"),
    ("packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php", "packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"),
    ("packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php", "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php"),
    ("packages/Webkul/Procurement/src/DataGrids/ProcurementBatchDataGrid.php", "packages/Webkul/Procurement/src/DataGrids/ProcurementBatchDataGrid.php"),
    ("packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php"),
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

# Clear views and cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Check batch 84 and state constant resolution
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementBatchController;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\ViewErrorBag;

echo "=========================================================\\n";
echo "1. VERIFYING STATE_PARTIALLY_SUBMITTED CONSTANT\\n";
echo "=========================================================\\n";
echo "Constant Value: " . ProcurementBatch::STATE_PARTIALLY_SUBMITTED . "\\n";
echo "Translation AR: " . trans('procurement::app.states.partially_submitted') . "\\n";
echo "Translation EN: " . trans('procurement::app.states.partially_submitted', [], 'en') . "\\n";

echo "\\n=========================================================\\n";
echo "2. VERIFYING BATCH #84 VIEW RENDERING WITH NEW FIX\\n";
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
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_partially_submitted_fix.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_partially_submitted_fix.php && rm test_partially_submitted_fix.php")
print(f"\nVerification Output:\n{out}")

client.close()
