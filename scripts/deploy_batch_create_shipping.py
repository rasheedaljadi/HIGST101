import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Resources/views/admin/batches/create.blade.php", "packages/Webkul/Procurement/src/Resources/views/admin/batches/create.blade.php"),
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

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementBatchController;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Support\\ViewErrorBag;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. TESTING BATCHES CREATE CONTROLLER & VIEW RENDER\\n";
echo "=========================================================\\n";
$controller = app(ProcurementBatchController::class);
$resp = $controller->create();

$html = $resp->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";
echo "Contains 'رسوم الشحن': " . (str_contains($html, 'رسوم الشحن') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'الإجمالي (شامل الشحن)': " . (str_contains($html, 'الإجمالي (شامل الشحن)') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains '+$61.38': " . (str_contains($html, '+$61.38') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains '$540.10': " . (str_contains($html, '$540.10') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'selected-total-cost': " . (str_contains($html, 'selected-total-cost') ? "YES ✅" : "NO ❌") . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_create_batch_view.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_create_batch_view.php && rm test_create_batch_view.php")
print(f"\nVerification Output:\n{out}")

client.close()
