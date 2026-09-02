import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Services\\ProcurementSubmitService;
use Webkul\\User\\Models\\Admin;

$batchNumber = 'BATCH-20260830-ECLGAS';
$batch = ProcurementBatch::with(['supplierOrders.items'])->where('batch_number', $batchNumber)->first();

if (!$batch) {
    echo "Batch {$batchNumber} not found!\\n";
    exit;
}

echo "=========================================================\\n";
echo "BATCH DETAILS: {$batchNumber}\\n";
echo "=========================================================\\n";
echo "ID: {$batch->id} | State: {$batch->state} | Currency: {$batch->currency_code}\\n";
echo "Created By: {$batch->created_by} | Approved By: {$batch->approved_by} | Approved At: {$batch->approved_at}\\n";

foreach ($batch->supplierOrders as $spo) {
    echo "\\nSPO ID: {$spo->id} | PO: {$spo->purchase_order_number} | State: {$spo->state}\\n";
    echo "  - Store: {$spo->supplier_store_name} (ID: {$spo->supplier_store_id})\\n";
    foreach ($spo->items as $item) {
        echo "    * Product ID: {$item->supplier_product_id} | SKU: {$item->supplier_sku_id} | Qty: {$item->qty_ordered} | Cost: \${$item->expected_unit_cost}\\n";
    }
}

echo "\\n=========================================================\\n";
echo "SIMULATING SUBMIT BATCH CALL TO CAPTURE EXACT EXCEPTION\\n";
echo "=========================================================\\n";

$admin = Admin::first();
if ($admin) {
    auth()->guard('admin')->setUser($admin);
}

$submitService = app(ProcurementSubmitService::class);
try {
    $res = $submitService->submitBatch($batch->id, $admin->id ?? 1);
    echo "Submit SUCCESS! New State: {$res->state}\\n";
} catch (\\Throwable $e) {
    echo "Exception Caught!\\n";
    echo "Class: " . get_class($e) . "\\n";
    echo "Message: " . $e->getMessage() . "\\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\\n";
    echo "Trace:\\n" . $e->getTraceAsString() . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_diagnose.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_diagnose.php && rm test_batch_diagnose.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
