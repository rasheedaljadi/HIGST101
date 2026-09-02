import remote_ssh_helper as r
import os

client = r.get_ssh_client()
sftp = client.open_sftp()

files_to_sync = [
    ("packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php", "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php"),
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

# Verification on server: Render batch 85 view and verify both buttons exist
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

$controller = app(ProcurementBatchController::class);
$resp = $controller->view(85);
$html = $resp->render();

echo "=========================================================\\n";
echo "VERIFYING BUTTONS ON BATCH #85 (APPROVED STATE)\\n";
echo "=========================================================\\n";

if (strpos($html, 'إعادة كامل الدفعة إلى مرحلة التجميع') !== false) {
    echo "1. Button 'إعادة كامل الدفعة إلى مرحلة التجميع' in Header: FOUND ✅\\n";
} else {
    echo "1. Button 'إعادة كامل الدفعة إلى مرحلة التجميع' in Header: NOT FOUND ❌\\n";
}

if (strpos($html, 'إزالة وإعادة لاحتياجات الشراء') !== false) {
    echo "2. Button 'إزالة وإعادة لاحتياجات الشراء' on SPO cards: FOUND ✅\\n";
} else {
    echo "2. Button 'إزالة وإعادة لاحتياجات الشراء' on SPO cards: NOT FOUND ❌\\n";
}
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_return_buttons.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_return_buttons.php && rm test_return_buttons.php")
print(f"\nVerification Output:\n{out}")

client.close()
