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
use Webkul\Core\Models\Channel;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Webkul\Product\Models\Product;
use App\Models\AliExpressProductImport;

echo "=== 1. UPDATING INVENTORY SOURCES ===" . PHP_EOL;

// 1. Ensure default is salable
DB::table('inventory_sources')->where('code', 'default')->update([
    'is_salable' => 1,
    'status' => 1,
]);

// 2. Ensure hayest_central is salable
DB::table('inventory_sources')->where('code', 'hayest_central')->update([
    'is_salable' => 1,
    'status' => 1,
]);

// 3. Ensure hayest_dropship_ye and hayest_internal_ye are salable
DB::table('inventory_sources')->whereIn('code', ['hayest_dropship_ye', 'hayest_internal_ye'])->update([
    'is_salable' => 1,
    'status' => 1,
]);

// 4. Link channel to all active sources
$channel = Channel::first();
if ($channel) {
    $salableSourceIds = DB::table('inventory_sources')
        ->whereIn('code', ['default', 'hayest_central', 'hayest_dropship_ye', 'hayest_internal_ye'])
        ->pluck('id')
        ->toArray();
    
    foreach ($salableSourceIds as $srcId) {
        DB::table('channel_inventory_sources')->updateOrInsert(
            ['channel_id' => $channel->id, 'inventory_source_id' => $srcId]
        );
    }
}

echo "=== 2. RUNNING INVENTORY REINDEX ===" . PHP_EOL;
$indexer = app(InventoryIndexer::class);
$indexer->reindexFull();

echo "=== 3. VERIFYING LAST 5 IMPORTS ===" . PHP_EOL;
$last5 = AliExpressProductImport::latest('id')->take(5)->get();

foreach ($last5 as $imp) {
    $prod = Product::with(['variants', 'inventories'])->find($imp->product_id);
    if (! $prod) continue;
    
    $masterIndex = DB::table('product_inventory_indices')->where('product_id', $prod->id)->first();
    $isSaleable = $prod->isSaleable();
    $haveQty = $prod->haveSufficientQuantity(1);
    
    echo "PROD_ID: {$prod->id} | SKU: {$prod->sku} | TYPE: {$prod->type} | ISSALEABLE: " . ($isSaleable ? 'YES' : 'NO') . " | HAVE_QTY: " . ($haveQty ? 'YES' : 'NO') . " | INDEX_QTY: " . ($masterIndex->qty ?? 0) . PHP_EOL;
    
    if ($prod->type === 'configurable') {
        foreach ($prod->variants as $v) {
            $vIndex = DB::table('product_inventory_indices')->where('product_id', $v->id)->first();
            $vSaleable = $v->isSaleable();
            echo "  VAR_ID: {$v->id} | SKU: {$v->sku} | ISSALEABLE: " . ($vSaleable ? 'YES' : 'NO') . " | INDEX_QTY: " . ($vIndex->qty ?? 0) . PHP_EOL;
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/apply_inventory_fix.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php apply_inventory_fix.php && rm apply_inventory_fix.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
