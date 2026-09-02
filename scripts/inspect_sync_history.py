import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductInventory;
use Webkul\Procurement\Models\ProcurementDemand;
use Illuminate\\Support\\Facades\\DB;

echo "=========================================================\\n";
echo "1. INVESTIGATING DEMANDS & THEIR STOCK CONFIGURATION\\n";
echo "=========================================================\\n";

$demands = ProcurementDemand::latest('id')->limit(10)->get();
foreach ($demands as $d) {
    $p = Product::find($d->product_id);
    $import = AliExpressProductImport::where('id', $d->source_snapshot['import_id'] ?? null)
        ->orWhere('aliexpress_product_id', $d->supplier_product_id)
        ->orWhere('product_id', $d->product_id)
        ->first();

    $localInventories = ProductInventory::where('product_id', $d->product_id)->get();
    $totalLocalQty = $localInventories->sum('qty');

    echo "Demand #{$d->id} (Order #{$d->order_id}) - Product #{$d->product_id}:\\n";
    echo "  - Supplier SKU: {$d->supplier_sku_id}\\n";
    echo "  - Local Bagisto Inventory Total Qty: {$totalLocalQty}\\n";
    if ($localInventories->isNotEmpty()) {
        foreach ($localInventories as $inv) {
            echo "    * InventorySource #{$inv->inventory_source_id}: qty={$inv->qty}, updated_at={$inv->updated_at}\\n";
        }
    }
    if ($p) {
        $pType = $p->type;
        echo "  - Bagisto Product Type: {$pType}\\n";
        if ($pType === 'configurable') {
            $variants = $p->variants()->with('inventories')->get();
            echo "  - Configurable Variants count: " . $variants->count() . "\\n";
            foreach ($variants as $v) {
                $vQty = $v->inventories->sum('qty');
                $vSku = $v->sku;
                echo "    * Variant #{$v->id} (SKU: {$vSku}): qty={$vQty}\\n";
            }
        }
    }
    if ($import) {
        echo "  - AliExpress Import #{$import->id} (AE ID: {$import->aliexpress_product_id}):\\n";
        echo "    * Import Status: {$import->status}\\n";
        echo "    * Created At: {$import->created_at}\\n";
        echo "    * Updated At: {$import->updated_at}\\n";
        echo "    * Last Synced At: " . ($import->last_synced_at ?? 'N/A') . "\\n";
    }
    echo "---------------------------------------------------------\\n";
}

echo "\\n=========================================================\\n";
echo "2. LAST SYNC RUNS IN DATABASE (sync_runs table)\\n";
echo "=========================================================\\n";
if (\\Schema::hasTable('sync_runs')) {
    $runs = DB::table('sync_runs')->latest('created_at')->limit(5)->get();
    if ($runs->isNotEmpty()) {
        foreach ($runs as $run) {
            echo "SyncRun ID: {$run->id}, Provider: {$run->provider}, Status: {$run->status}, Created: {$run->created_at}, Updated: {$run->updated_at}\\n";
            echo "  Metadata: {$run->metadata}\\n";
            echo "  Statistics: {$run->statistics}\\n";
        }
    } else {
        echo "No sync_runs records found.\\n";
    }
} else {
    echo "Table sync_runs does not exist.\\n";
}

echo "\\n=========================================================\\n";
echo "3. ALIEXPRESS SETTINGS & INVENTORY CONFIG\\n";
echo "=========================================================\\n";
$settings = AliExpressSetting::current();
if ($settings) {
    echo "sync_enabled: " . ($settings->sync_enabled ? 'YES' : 'NO') . "\\n";
    echo "sync_stock_enabled: " . ($settings->sync_stock_enabled ? 'YES' : 'NO') . "\\n";
    echo "sync_price_enabled: " . ($settings->sync_price_enabled ? 'YES' : 'NO') . "\\n";
    echo "auto_disable_oos: " . ($settings->auto_disable_oos ? 'YES' : 'NO') . "\\n";
    echo "Settings Updated At: {$settings->updated_at}\\n";
}

echo "\\n=========================================================\\n";
echo "4. CHECKING ALIEXPRESS LOG FILE TIMESTAMPS\\n";
echo "=========================================================\\n";
$logPath = storage_path('logs/aliexpress.log');
if (file_exists($logPath)) {
    echo "aliexpress.log size: " . filesize($logPath) . " bytes, last modified: " . date('Y-m-d H:i:s', filemtime($logPath)) . "\\n";
} else {
    echo "aliexpress.log does not exist.\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_sync_history.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_sync_history.php && rm inspect_sync_history.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
