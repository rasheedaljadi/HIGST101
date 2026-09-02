import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Services\\ProcurementBatchService;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\User\\Models\\Admin;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$batchService = app(ProcurementBatchService::class);
$openDemands = ProcurementDemand::where('state', 'open_for_batching')->get();

echo "Open Demands for testing: " . $openDemands->count() . "\\n";
foreach ($openDemands as $d) {
    echo "  - Demand ID: {$d->id} | Product ID: {$d->product_id} | Store: {$d->supplier_store_name}\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_shipping_preview.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_shipping_preview.php && rm test_batch_shipping_preview.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
