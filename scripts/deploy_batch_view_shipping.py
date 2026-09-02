import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
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

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test verification on server for Batch 82
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementBatchController;
use Webkul\\User\\Models\\Admin;
use Webkul\\Procurement\\Models\\ProcurementBatch;
use Illuminate\\Support\\ViewErrorBag;

view()->share('errors', new ViewErrorBag());
$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "1. TESTING BATCHES VIEW CONTROLLER & VIEW RENDER FOR BATCH #82\\n";
echo "=========================================================\\n";
$batch = ProcurementBatch::latest('id')->first();
$batchId = $batch ? $batch->id : 82;
echo "Testing Batch ID: {$batchId}\\n";

$controller = app(ProcurementBatchController::class);
$resp = $controller->view($batchId);

$html = $resp->render();
echo "Render Success! HTML Length: " . strlen($html) . " bytes\\n";
echo "Contains 'رسوم الشحن': " . (str_contains($html, 'رسوم الشحن') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains '+$61.38': " . (str_contains($html, '+$61.38') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains '$540.10': " . (str_contains($html, '$540.10') ? "YES ✅" : "NO ❌") . "\\n";
echo "Contains 'شحن: +$61.38': " . (str_contains($html, 'شحن: +$61.38') ? "YES ✅" : "NO ❌") . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_view_batch_shipping.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_view_batch_shipping.php && rm test_view_batch_shipping.php")
print(f"\nVerification Output:\n{out}")

client.close()
