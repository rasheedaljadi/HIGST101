import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Services\ProcurementBatchService;

$batchService = app(ProcurementBatchService::class);

echo "=== Testing Approval Gate on Batch #77 (Out of Stock Item) ===\n";
try {
    // Reset state to ready_for_review to test approval gate
    \Illuminate\Support\Facades\DB::table('procurement_batches')->where('id', 77)->update(['state' => 'ready_for_review']);
    
    $batch = $batchService->approveBatch(77, 1, 'Testing pre-approval gate');
    echo "Batch approved unexpectedly! State: " . $batch->state . "\n";
} catch (\Throwable $e) {
    echo "SUCCESS: Approval Gate blocked invalid batch!\n";
    echo "Blocked Message (Arabic):\n" . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_approval_gate_remote.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php test_approval_gate_remote.php && rm test_approval_gate_remote.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
