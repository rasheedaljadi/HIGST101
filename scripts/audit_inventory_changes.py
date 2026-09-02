import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use Illuminate\\Support\\Facades\\DB;

$startTime = '2026-08-30 02:38:00';
$endTime   = '2026-08-30 02:41:35';

$syncedImports = DB::table('aliexpress_product_imports')
    ->whereBetween('updated_at', [$startTime, $endTime])
    ->get();

echo "=========================================================\\n";
echo "AUDIT OF INVENTORY & STATUS CHANGES FOR SYNCED PRODUCTS\\n";
echo "=========================================================\\n";

$outOfStockCount = 0;
$inStockCount = 0;
$totalInventoryQty = 0;

$outOfStockList = [];
$inStockList = [];

foreach ($syncedImports as $imp) {
    $prod = DB::table('products')->where('id', $imp->product_id)->first();
    $prodFlat = DB::table('product_flat')->where('product_id', $imp->product_id)->first();
    $name = $prodFlat?->name ?? 'Product #' . $imp->product_id;

    // Check inventory of product and its variants
    $invQty = (int) DB::table('product_inventories')->where('product_id', $imp->product_id)->sum('qty');
    
    // Also check variants if configurable
    $variantIds = DB::table('products')->where('parent_id', $imp->product_id)->pluck('id');
    $variantInv = 0;
    if ($variantIds->isNotEmpty()) {
        $variantInv = (int) DB::table('product_inventories')->whereIn('product_id', $variantIds)->sum('qty');
    }
    
    $effectiveStock = max($invQty, $variantInv);
    $totalInventoryQty += $effectiveStock;

    if ($effectiveStock === 0) {
        $outOfStockCount++;
        $outOfStockList[] = [
            'id' => $imp->product_id,
            'ae_id' => $imp->aliexpress_product_id,
            'name' => $name,
            'stock' => 0,
            'variants_count' => $variantIds->count()
        ];
    } else {
        $inStockCount++;
        $inStockList[] = [
            'id' => $imp->product_id,
            'ae_id' => $imp->aliexpress_product_id,
            'name' => $name,
            'stock' => $effectiveStock,
            'variants_count' => $variantIds->count()
        ];
    }
}

echo "Total Products Processed in this Window: " . $syncedImports->count() . "\\n";
echo "In-Stock Products: {$inStockCount}\\n";
echo "Out-of-Stock Products (0 Stock): {$outOfStockCount}\\n";
echo "Total Cumulative Stock Synchronized: {$totalInventoryQty} units\\n";

echo "\\n--- [ OUT-OF-STOCK PRODUCTS (0 Stock at AliExpress) ] ---\\n";
foreach ($outOfStockList as $o) {
    echo "  🛑 Product #{$o['id']} (AE: {$o['ae_id']}) [Variants: {$o['variants_count']}]: {$o['name']}\\n";
}

echo "\\n--- [ SAMPLE OF IN-STOCK PRODUCTS ] ---\\n";
foreach (array_slice($inStockList, 0, 8) as $i) {
    echo "  ✅ Product #{$i['id']} (AE: {$i['ae_id']}) -> Stock: {$i['stock']} [Variants: {$i['variants_count']}]: {$i['name']}\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/audit_inventory_changes.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 audit_inventory_changes.php && rm audit_inventory_changes.php")
print(f"OUT:\n{out}")

client.close()
