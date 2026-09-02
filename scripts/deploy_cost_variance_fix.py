import remote_ssh_helper as r

client = r.get_ssh_client()

files_to_sync = [
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Services\ProcurementBatchService.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementBatchService.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Services\ProcurementSubmitService.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Services\ProcurementVarianceApprovalService.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Services/ProcurementVarianceApprovalService.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\DataGrids\CostVarianceDataGrid.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/DataGrids/CostVarianceDataGrid.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\views\admin\supplier_orders\view.blade.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Resources/views/admin/supplier_orders/view.blade.php"
    ),
    (
        r"e:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src\Resources\views\admin\batches\view.blade.php",
        "/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Procurement/src/Resources/views/admin/batches/view.blade.php"
    ),
]

sftp = client.open_sftp()
for local, remote in files_to_sync:
    print(f"Uploading {local} -> {remote}...")
    sftp.put(local, remote)
sftp.close()
print("All files uploaded successfully!")

# Clear cache on remote
code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php artisan optimize:clear")
print(f"Clear cache output:\n{out}")

# Now, trigger approval on Batch #79 to let the new logic automatically transition SPO #100 into cost_variance_review and commit it!
php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\ProcurementBatchService;
use Illuminate\Support\Facades\DB;

$batchService = app(ProcurementBatchService::class);

echo "=== Attempting to approve Batch #79 (SPO #100 with price variance) ===\n";
try {
    $batchService->approveBatch(79, 1);
    echo "Batch approved!\n";
} catch (\Throwable $e) {
    echo "Approval Gate Caught Variance: " . $e->getMessage() . "\n";
}

echo "\n=== Verifying SPO #100 in database ===\n";
$spo = DB::table('supplier_purchase_orders')->where('id', 100)->first();
echo "SPO ID: {$spo->id}, State: {$spo->state}, Variance Amount: {$spo->cost_variance_amount}, Expected: {$spo->expected_total}\n";

echo "\n=== Verifying CostVarianceDataGrid query ===\n";
$cvs = DB::table('supplier_purchase_orders')->where('state', 'cost_variance_review')->get();
echo "Found " . $cvs->count() . " SPOs in cost_variance_review:\n";
foreach ($cvs as $c) {
    echo "- SPO #{$c->id} ({$c->purchase_order_number}): Expected \${$c->expected_total}, Variance: \${$c->cost_variance_amount}, State: {$c->state}\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_cost_variance_flow.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_cost_variance_flow.php && rm test_cost_variance_flow.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
