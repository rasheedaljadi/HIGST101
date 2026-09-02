import sys
sys.path.insert(0, 'scripts')
import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_code = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
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
$demandIds = [66, 70];

echo "=========================================================\\n";
echo "1. PREVIEWING HYBRID BATCH FOR DEMANDS: [63, 65, 66, 70]\\n";
echo "=========================================================\\n";

$preview = $batchService->previewBatch($demandIds);
echo "Demands Count: " . $preview['demands_count'] . "\\n";
echo "Total Items Count: " . $preview['total_items_count'] . "\\n";
echo "Distinct Stores Count: " . $preview['stores_count'] . "\\n";
echo "Expected Total Cost: $" . $preview['expected_total_cost'] . " " . $preview['currency'] . "\\n\\n";

echo "Stores Breakdown:\\n";
foreach ($preview['stores'] as $st) {
    echo "  - Store [{$st['store_id']}] {$st['store_name']}:\\n";
    echo "      Total Demands: {$st['total_demands']}, Total Qty: {$st['total_qty']}, Expected Cost: \${$st['expected_cost']}\\n";
    foreach ($st['items'] as $it) {
        echo "        * Demand #{$it['demand_id']} (Order #{$it['order_id']}) -> SKU: {$it['supplier_sku_id']}, Qty: {$it['qty']}, Unit Cost: \${$it['unit_cost']}\\n";
    }
}

echo "\\n=========================================================\\n";
echo "2. DRY-RUN EXECUTION: CREATING BATCH & CHECKING INVARIANTS\\n";
echo "=========================================================\\n";

DB::beginTransaction();
try {
    $batch = $batchService->createBatch($demandIds, $admin->id);
    echo "Batch Created: ID #{$batch->id} ({$batch->batch_code})\\n";
    echo "Batch State: {$batch->state}\\n";
    echo "Batch Total: \${$batch->expected_total_cost}\\n";
    echo "Generated Supplier POs Count: " . $batch->supplierOrders->count() . "\\n";

    foreach ($batch->supplierOrders as $spo) {
        echo "\\n--- Supplier PO: {$spo->purchase_order_number} ---\\n";
        echo "  Store: {$spo->supplier_store_id} ({$spo->supplier_store_name})\\n";
        echo "  Items Total: \${$spo->expected_items_total}, Shipping: \${$spo->expected_shipping_total}, Grand Total: \${$spo->expected_total}\\n";
        echo "  Items Count: " . $spo->items->count() . "\\n";
        foreach ($spo->items as $item) {
            echo "    * SPO Item #{$item->id}: SKU {$item->supplier_sku_id}, Ordered Qty: {$item->qty_ordered}, Unit Cost: \${$item->expected_unit_cost}\\n";
        }

        echo "  >> Testing Live Preflight on SPO #{$spo->id}...\\n";
        $submitService = app(\\Webkul\\Procurement\\Services\\ProcurementSubmitService::class);
        try {
            $preflight = $submitService->preflightSupplierPurchaseOrder($spo->id);
            echo "     Preflight Result: " . ($preflight->isSuccess ? "SUCCESS (Deliverable: YES)" : "FAILED: {$preflight->errorMessage} [{$preflight->errorCode}]") . "\\n";
            if ($preflight->isSuccess) {
                echo "     Carrier: {$preflight->shippingServiceName}, Cost: \${$preflight->shippingCost}, Delivery: {$preflight->minDeliveryDays}-{$preflight->maxDeliveryDays} days\\n";
            }
        } catch (Throwable $pe) {
            echo "     Preflight Exception: " . $pe->getMessage() . "\\n";
        }
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\\n" . $e->getTraceAsString() . "\\n";
} finally {
    DB::rollBack();
    echo "\\n[TRANSACTION ROLLED BACK SAFELY: Zero production DB mutation]\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_hybrid_simulation.php", "w") as f:
    f.write(php_code)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_hybrid_simulation.php && rm test_hybrid_simulation.php")
print(out)
client.close()
