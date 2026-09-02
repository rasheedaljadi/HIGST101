import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Services\ProcurementVarianceApprovalService;
use Illuminate\Support\Facades\DB;

$varianceService = app(ProcurementVarianceApprovalService::class);

echo "=== Testing approveVariance on SPO #100 ===\n";
$spo = DB::table('supplier_purchase_orders')->where('id', 100)->first();
echo "Before Approval: State = {$spo->state}, Expected Total = {$spo->expected_total}, Variance = {$spo->cost_variance_amount}\n";

$approvedSpo = $varianceService->approveVariance(100, 1, 'Approved live price update to $1047.12');
echo "After Approval: State = {$approvedSpo->state}, Expected Total = {$approvedSpo->expected_total}, Variance = {$approvedSpo->cost_variance_amount}\n";

$batch = DB::table('procurement_batches')->where('id', 79)->first();
echo "Batch #79 State = {$batch->state}, Expected Total Cost = {$batch->expected_total_cost}\n";

// Reset back to cost_variance_review for the user so they can test/see it in the UI and click the button themselves!
DB::table('supplier_purchase_orders')->where('id', 100)->update([
    'state' => 'cost_variance_review',
    'cost_variance_amount' => 168.1200,
    'expected_items_total' => 879.0000,
    'expected_total' => 879.0000,
]);
DB::table('procurement_batches')->where('id', 79)->update([
    'state' => 'exception',
    'expected_total_cost' => 879.0000,
]);
echo "\nReset SPO #100 back to cost_variance_review so user can test the UI buttons in admin dashboard!\n";
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_variance_approval_action.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_variance_approval_action.php && rm test_variance_approval_action.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
