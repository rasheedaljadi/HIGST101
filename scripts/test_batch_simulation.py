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
use Illuminate\\Support\\Facades\\DB;

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$batchService = app(ProcurementBatchService::class);

DB::beginTransaction();

try {
    $batch = $batchService->createBatch([59], $admin->id);
    
    echo "=========================================================\\n";
    echo "BATCH CREATED SUCCESSFULLY: Batch #{$batch->id}\\n";
    echo "=========================================================\\n";
    echo "Batch Expected Total Cost: $" . $batch->expected_total_cost . "\\n";
    
    foreach ($batch->supplierOrders as $spo) {
        echo "\\nSupplier Purchase Order: {$spo->purchase_order_number}\\n";
        echo "  - Store: {$spo->supplier_store_name}\\n";
        echo "  - Expected Items Total: $" . $spo->expected_items_total . "\\n";
        echo "  - Expected Shipping Total: $" . $spo->expected_shipping_total . "\\n";
        echo "  - Expected Grand Total: $" . $spo->expected_total . "\\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\\n";
} finally {
    DB::rollBack();
    echo "\\n[DB Rolled Back successfully for test isolation]\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_simulation.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_simulation.php && rm test_batch_simulation.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
