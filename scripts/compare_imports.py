import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use App\Models\AliExpressProductImport;
use Webkul\Product\Models\Product;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;
use Illuminate\Support\Facades\DB;

$imports = AliExpressProductImport::latest('id')->take(10)->get();

foreach ($imports as $imp) {
    $p = Product::find($imp->product_id);
    if (! $p) continue;
    
    $masterIndex = DB::table('product_inventory_indices')->where('product_id', $p->id)->first();
    $masterInv = DB::table('product_inventories')->where('product_id', $p->id)->get();
    
    echo "PROD_ID: {$p->id} | IMP_ID: {$imp->id} | TYPE: {$p->type} | CREATED: {$imp->created_at}" . PHP_EOL;
    echo "  MASTER_INDICES: " . json_encode($masterIndex) . PHP_EOL;
    echo "  MASTER_INVENTORIES: " . json_encode($masterInv->toArray()) . PHP_EOL;
    
    if ($p->type === 'configurable') {
        foreach ($p->variants as $v) {
            $vIndex = DB::table('product_inventory_indices')->where('product_id', $v->id)->first();
            $vInv = DB::table('product_inventories')->where('product_id', $v->id)->get();
            echo "    VAR_ID: {$v->id} | SKU: {$v->sku} | INDEX_QTY: " . ($vIndex->qty ?? 'NULL') . " | INV: " . json_encode($vInv->toArray()) . PHP_EOL;
        }
    }
}

// Let's test running inventoryIndexer on one of the last products!
$lastProd = Product::find($imports->first()->product_id);
$indexer = app(InventoryIndexer::class);
echo "REINDEXING PROD: {$lastProd->id}..." . PHP_EOL;
$indexer->reindexBatch([$lastProd->id]);
if ($lastProd->type === 'configurable') {
    $indexer->reindexBatch($lastProd->variants->pluck('id')->toArray());
}

$afterIndex = DB::table('product_inventory_indices')->where('product_id', $lastProd->id)->first();
echo "AFTER_REINDEX_MASTER: " . json_encode($afterIndex) . PHP_EOL;
if ($lastProd->type === 'configurable') {
    foreach ($lastProd->variants as $v) {
        $vAfterIndex = DB::table('product_inventory_indices')->where('product_id', $v->id)->first();
        echo "  AFTER_REINDEX_VAR: {$v->id} | INDEX_QTY: " . ($vAfterIndex->qty ?? 'NULL') . PHP_EOL;
    }
}
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/compare_imports.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php compare_imports.php && rm compare_imports.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
