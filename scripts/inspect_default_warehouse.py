import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\Product;

$defaultSource = InventorySource::where('code', 'default')->first();
if (! $defaultSource) {
    echo "DEFAULT_SOURCE_NOT_FOUND" . PHP_EOL;
    exit;
}

echo "SOURCE_DETAILS:" . json_encode($defaultSource->toArray()) . PHP_EOL;

// 1. Overall counts in product_inventories
$totalRows = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->count();
$totalQty = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->sum('qty');
$positiveRows = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->where('qty', '>', 0)->count();
$zeroRows = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->where('qty', '<=', 0)->count();

echo "TOTAL_INVENTORY_ROWS: $totalRows" . PHP_EOL;
echo "TOTAL_QUANTITY_SUM: $totalQty" . PHP_EOL;
echo "POSITIVE_ROWS: $positiveRows" . PHP_EOL;
echo "ZERO_ROWS: $zeroRows" . PHP_EOL;

// 2. Breakdown by product origin (AliExpress vs Internal)
$aliExpressImportsProductIds = DB::table('aliexpress_product_imports')->pluck('product_id')->filter()->toArray();

// Also check variant IDs belonging to AliExpress products
$aliExpressVariantIds = DB::table('products')
    ->whereIn('parent_id', $aliExpressImportsProductIds)
    ->pluck('id')
    ->toArray();

$allAeProductAndVariantIds = array_unique(array_merge($aliExpressImportsProductIds, $aliExpressVariantIds));

$aeRows = DB::table('product_inventories')
    ->where('inventory_source_id', $defaultSource->id)
    ->whereIn('product_id', $allAeProductAndVariantIds)
    ->count();
$aeQty = DB::table('product_inventories')
    ->where('inventory_source_id', $defaultSource->id)
    ->whereIn('product_id', $allAeProductAndVariantIds)
    ->sum('qty');

$internalRows = DB::table('product_inventories')
    ->where('inventory_source_id', $defaultSource->id)
    ->whereNotIn('product_id', $allAeProductAndVariantIds)
    ->count();
$internalQty = DB::table('product_inventories')
    ->where('inventory_source_id', $defaultSource->id)
    ->whereNotIn('product_id', $allAeProductAndVariantIds)
    ->sum('qty');

echo "ALIEXPRESS_ROWS_COUNT: $aeRows | ALIEXPRESS_TOTAL_QTY: $aeQty" . PHP_EOL;
echo "INTERNAL_ROWS_COUNT: $internalRows | INTERNAL_TOTAL_QTY: $internalQty" . PHP_EOL;

// 3. List internal products in default warehouse
echo "=== INTERNAL PRODUCTS IN DEFAULT WAREHOUSE ===" . PHP_EOL;
$internalProducts = DB::table('product_inventories as pi')
    ->join('products as p', 'pi.product_id', '=', 'p.id')
    ->leftJoin('product_flat as pf', function($join) {
        $join->on('p.id', '=', 'pf.product_id')->where('pf.locale', '=', 'ar');
    })
    ->where('pi.inventory_source_id', $defaultSource->id)
    ->whereNotIn('pi.product_id', $allAeProductAndVariantIds)
    ->select('p.id', 'p.sku', 'p.type', 'p.parent_id', 'pi.qty', 'pf.name')
    ->get();

foreach ($internalProducts as $ip) {
    echo "ID: {$ip->id} | SKU: {$ip->sku} | TYPE: {$ip->type} | PARENT_ID: {$ip->parent_id} | QTY: {$ip->qty} | NAME: " . ($ip->name ?? 'N/A') . PHP_EOL;
}

// 4. Sample top 10 AliExpress products in default warehouse
echo "=== TOP 10 ALIEXPRESS PRODUCTS BY QTY ===" . PHP_EOL;
$topAe = DB::table('product_inventories as pi')
    ->join('products as p', 'pi.product_id', '=', 'p.id')
    ->leftJoin('product_flat as pf', function($join) {
        $join->on('p.id', '=', 'pf.product_id')->where('pf.locale', '=', 'ar');
    })
    ->where('pi.inventory_source_id', $defaultSource->id)
    ->whereIn('pi.product_id', $allAeProductAndVariantIds)
    ->orderByDesc('pi.qty')
    ->take(10)
    ->select('p.id', 'p.sku', 'p.type', 'pi.qty', 'pf.name')
    ->get();

foreach ($topAe as $ae) {
    echo "ID: {$ae->id} | SKU: {$ae->sku} | QTY: {$ae->qty} | NAME: " . ($ae->name ?? 'N/A') . PHP_EOL;
}
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/inspect_default_warehouse.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php inspect_default_warehouse.php && rm inspect_default_warehouse.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
