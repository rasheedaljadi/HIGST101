import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementBatch;
use Webkul\\Procurement\\Models\\SupplierPurchaseOrder;

$batch = ProcurementBatch::with(['supplierOrders.items'])->find(84);
if ($batch) {
    echo "=========================================================\\n";
    echo "BATCH #84 DETAILS:\\n";
    echo "=========================================================\\n";
    echo "Batch ID: {$batch->id} | Number: {$batch->batch_number} | State: {$batch->state}\\n";
    
    foreach ($batch->supplierOrders as $spo) {
        echo "\\nSPO ID: {$spo->id} | Number: {$spo->purchase_order_number} | State: {$spo->state}\\n";
        echo "  - Store: {$spo->supplier_store_name}\\n";
        echo "  - Submission Notes / Rejection: " . ($spo->rejection_reason ?? 'None') . "\\n";
        foreach ($spo->items as $item) {
            echo "    * SKU: {$item->supplier_sku_id} | Product ID: {$item->supplier_product_id} | Qty: {$item->qty_ordered} | Cost: \${$item->expected_unit_cost}\\n";
        }
    }
} else {
    echo "Batch #84 not found.\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_84_inspection.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_84_inspection.php && rm test_batch_84_inspection.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
