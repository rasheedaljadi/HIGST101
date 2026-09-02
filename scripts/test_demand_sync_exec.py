import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
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
echo "SIMULATING CALL TO syncStock ACTION\\n";
echo "=========================================================\\n";

$controller = app(ProcurementDemandController::class);
$syncer = app(AliExpressProductSyncer::class);
$request = Request::create('/admin/dropshipping/procurement-v2/demands/sync-stock', 'POST');

try {
    $resp = $controller->syncStock($request, $syncer);
    echo "Response Class: " . get_class($resp) . "\\n";
    if (method_exists($resp, 'getTargetUrl')) {
        echo "Target Redirect URL: " . $resp->getTargetUrl() . "\\n";
    }
    echo "Session Flashes:\\n";
    print_r(session()->all());
} catch (\\Throwable $e) {
    echo "Exception Caught:\\n";
    echo get_class($e) . ": " . $e->getMessage() . "\\n";
    echo $e->getFile() . ":" . $e->getLine() . "\\n";
    echo $e->getTraceAsString() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_demand_sync_exec.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_demand_sync_exec.php && rm test_demand_sync_exec.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
