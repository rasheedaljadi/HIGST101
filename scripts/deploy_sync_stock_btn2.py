import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

rel_path = "packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php"
sftp.put(f"{local_base}/{rel_path}", f"{remote_base}/{rel_path}")
sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Verification on server: Render Demands view and verify Sync Stock button
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
echo "VERIFYING SYNC STOCK BUTTON IN DEMANDS VIEW\\n";
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
with sftp2.file(f"{remote_base}/test_sync_stock_btn2.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_sync_stock_btn2.php && rm test_sync_stock_btn2.php")
print(f"\nVerification Output:\n{out}")

client.close()
