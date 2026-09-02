import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

local_base = r"e:\HIGESTO NEW1\higest\higest101"
remote_base = "/home/highest-ye/htdocs/highest-ye.store"

files = [
    "packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php",
    "packages/Webkul/Procurement/src/Resources/views/admin/demands/index.blade.php",
]

for f in files:
    sftp.put(f"{local_base}/{f}", f"{remote_base}/{f}")

sftp.close()

# Clear cache
code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 artisan view:clear && php8.4 artisan cache:clear")
print(f"Cache Clear: CODE {code}\n{out}")

# Test AJAX call simulation on production
test_php = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Http\\Controllers\\Admin\\ProcurementDemandController;
use App\\Services\\AliExpress\\AliExpressProductSyncer;
use Webkul\\User\\Models\\Admin;
use Illuminate\\Http\\Request;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

echo "=========================================================\\n";
echo "TESTING AJAX STOCK SYNC CALL ON SERVER\\n";
echo "=========================================================\\n";
$controller = app(ProcurementDemandController::class);
$syncer = app(AliExpressProductSyncer::class);

$req = Request::create(
    '/admin/dropshipping/procurement-v2/demands/sync-stock',
    'POST',
    [],
    [],
    [],
    ['HTTP_X-Requested-With' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']
);

$res = $controller->syncStock($req, $syncer);
echo "Status Code: " . $res->getStatusCode() . "\\n";
echo "JSON Content: " . $res->getContent() . "\\n";
"""

sftp2 = client.open_sftp()
with sftp2.file(f"{remote_base}/test_ajax_sync.php", "w") as f:
    f.write(test_php)
sftp2.close()

code, out, err = r.run_remote_cmd(client, f"cd {remote_base} && php8.4 test_ajax_sync.php && rm test_ajax_sync.php")
print(f"\nVerification Output:\n{out}")

client.close()
