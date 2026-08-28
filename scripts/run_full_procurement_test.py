import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Models\SupplierPurchaseOrderItem;
use Webkul\Procurement\Services\ProcurementSubmitService;
use Illuminate\Support\Facades\DB;

echo "=== EXECUTING COMPLETE TEST PURCHASE ORDER (SPO) ===\n";

$productId = '1005010737996063';
$skuId = '12000053357140815';

// 1. Create a Procurement Batch
$batchNumber = 'BATCH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$batch = ProcurementBatch::create([
    'batch_number' => $batchNumber,
    'state' => ProcurementBatch::STATE_APPROVED,
    'currency_code' => 'USD',
    'created_by' => 1,
    'total_amount' => 49.79,
]);
echo "1. Created Batch #{$batch->id} ({$batch->batch_number})\n";

// 2. Create Supplier Purchase Order (SPO)
$spoNumber = 'SPO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)) . '-01';
$spo = SupplierPurchaseOrder::create([
    'purchase_order_number' => $spoNumber,
    'batch_id' => $batch->id,
    'state' => SupplierPurchaseOrder::STATE_READY_TO_SUBMIT,
    'provider' => 'aliexpress',
    'currency_code' => 'USD',
    'expected_items_total' => 44.79,
    'expected_shipping_total' => 5.00,
    'expected_total' => 49.79,
    'max_authorized_total' => 55.00,
]);
echo "2. Created SPO #{$spo->id} ({$spo->purchase_order_number})\n";

// 3. Create SPO Item
$spoItem = SupplierPurchaseOrderItem::create([
    'supplier_purchase_order_id' => $spo->id,
    'product_id' => 1,
    'supplier_product_id' => $productId,
    'supplier_sku_id' => $skuId,
    'quantity_demanded' => 1,
    'quantity_ordered' => 1,
    'expected_unit_cost' => 44.79,
    'expected_shipping_cost' => 5.00,
    'raw_payload' => ['sku_attr' => '14:201447015#NO PAD'],
]);
echo "3. Created SPO Item #{$spoItem->id} for Product {$productId}\n";

// 4. Submit Order to AliExpress via ProcurementSubmitService
echo "4. Executing submitSupplierPurchaseOrder()...\n";
$submitService = app(ProcurementSubmitService::class);

try {
    $updatedSpo = $submitService->submitSupplierPurchaseOrder($spo->id, 1);
    
    echo "\n🎉🎉🎉 PURCHASE ORDER SUBMITTED SUCCESSFULLY! 🎉🎉🎉\n";
    echo "SPO ID: {$updatedSpo->id}\n";
    echo "SPO Number: {$updatedSpo->purchase_order_number}\n";
    echo "SPO State: {$updatedSpo->state}\n";
    
    $platformOrder = DB::table('external_platform_orders')
        ->where('supplier_purchase_order_id', $updatedSpo->id)
        ->first();
        
    if ($platformOrder) {
        echo "\n=== ALIEXPRESS EXTERNAL PLATFORM ORDER ===\n";
        echo "EPO ID: {$platformOrder->id}\n";
        echo "Official AliExpress External Order ID: {$platformOrder->external_order_id}\n";
        echo "Platform Order Status: {$platformOrder->platform_order_status}\n";
        echo "Sync Status: {$platformOrder->sync_status}\n";
        echo "Created At: {$platformOrder->created_at}\n";
    }
} catch (\Throwable $e) {
    echo "Submission error: " . $e->getMessage() . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/run_full_procurement_test.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 run_full_procurement_test.php && rm run_full_procurement_test.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
