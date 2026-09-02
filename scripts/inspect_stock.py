import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Illuminate\\Support\\Facades\\DB;

$demands = ProcurementDemand::latest('id')->limit(5)->get();

echo "=========================================================\\n";
echo "LATEST DEMANDS & CORRESPONDING ALIEXPRESS IMPORTS:\\n";
echo "=========================================================\\n";

foreach ($demands as $d) {
    echo "\\nDemand ID: {$d->id} | Product ID: {$d->product_id} | Supplier Product ID: {$d->supplier_product_id} | SKU: {$d->supplier_sku_id}\\n";
    echo "  State: {$d->state} | Qty Required: {$d->qty_required_external}\\n";
    
    $import = AliExpressProductImport::where('aliexpress_product_id', $d->supplier_product_id)
        ->orWhere('product_id', $d->product_id)
        ->latest('id')
        ->first();
        
    if ($import) {
        echo "  Import Found: ID {$import->id}\\n";
        $snap = $import->payload_snapshot;
        $variants = $snap['variants'] ?? [];
        echo "  Total Variants in Snapshot: " . count($variants) . "\\n";
        foreach ($variants as $v) {
            $sId = (string)($v['sku_id'] ?? $v['id'] ?? '');
            if ($sId == $d->supplier_sku_id || count($variants) == 1) {
                echo "    * Matching Variant SKU {$sId}: Stock=" . ($v['stock'] ?? $v['quantity'] ?? $v['sku_stock'] ?? 'NULL') . " | Price=" . ($v['price'] ?? $v['offer_sale_price'] ?? 'NULL') . "\\n";
            }
        }
    } else {
        echo "  No AliExpressProductImport found.\\n";
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_stock_inspection.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_stock_inspection.php && rm test_stock_inspection.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
