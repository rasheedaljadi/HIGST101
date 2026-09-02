import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

rel_path = "packages/Webkul/Procurement/src/Resources/views/admin/batches/create.blade.php"
sftp.put(f"{local_base}/{rel_path}", f"{remote_base}/{rel_path}")
sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Render Create Batch view
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
$resp = $controller->create();
$html = $resp->render();

echo "=========================================================\\n";
echo "VERIFYING PRODUCT NAME & VARIANTS IN BATCH CREATE VIEW\\n";
echo "=========================================================\\n";
if (strpos($html, 'اسم المنتج والمتغيرات') !== false) {
    echo "Product Name & Variants Header: FOUND ✅\\n";
} else {
    echo "Product Name & Variants Header: NOT FOUND ❌\\n";
}
echo "Rendered HTML length: " . strlen($html) . " bytes\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_create_view.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_create_view.php && rm test_create_view.php")
print(f"\nVerification Output:\n{out}")

client.close()
