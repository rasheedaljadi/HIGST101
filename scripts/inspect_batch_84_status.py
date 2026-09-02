import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Webkul\\Procurement\\Models\\ProcurementBatch;

$batch = ProcurementBatch::with(['supplierOrders.platformOrders'])->find(84);
echo "=========================================================\\n";
echo "BATCH #84 CURRENT STATUS:\\n";
echo "=========================================================\\n";
echo "Batch ID: {$batch->id} | Number: {$batch->batch_number} | State: {$batch->state}\\n";

foreach ($batch->supplierOrders as $spo) {
    echo "\\nSPO ID: {$spo->id} | Number: {$spo->purchase_order_number} | State: {$spo->state}\\n";
    echo "  - Store: {$spo->supplier_store_name}\\n";
    foreach ($spo->platformOrders as $po) {
        echo "    * Platform Order ID: {$po->id} | Ext Order ID: {$po->external_order_id} | Status: {$po->normalized_status} | Fail Msg: {$po->failure_message}\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_batch_84_status.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_batch_84_status.php && rm test_batch_84_status.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
