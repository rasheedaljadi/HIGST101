import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

php_script = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use Webkul\Product\Models\Product;
use Webkul\Inventory\Models\InventorySource;
use Illuminate\Support\Facades\DB;

$setting = AliExpressSetting::current();
echo 'ALIEXPRESS_SETTING:' . json_encode($setting ? $setting->toArray() : []) . PHP_EOL;

$sources = InventorySource::all();
echo 'INVENTORY_SOURCES:' . json_encode($sources->toArray()) . PHP_EOL;

$channelSources = DB::table('channel_inventory_sources')->get();
echo 'CHANNEL_INVENTORY_SOURCES:' . json_encode($channelSources->toArray()) . PHP_EOL;

$last5Imports = AliExpressProductImport::latest('id')->take(5)->get();

foreach ($last5Imports as $imp) {
    echo '=========================================' . PHP_EOL;
    echo 'IMPORT_ID:' . $imp->id . ' | AE_ID:' . $imp->aliexpress_product_id . ' | PROD_ID:' . $imp->product_id . ' | STATUS:' . $imp->status . ' | CREATED:' . $imp->created_at . PHP_EOL;
    
    $product = Product::with(['variants', 'inventories'])->find($imp->product_id);
    if (! $product) {
        echo 'LOCAL_PRODUCT_NOT_FOUND' . PHP_EOL;
        continue;
    }
    
    echo 'PRODUCT_TYPE:' . $product->type . ' | SKU:' . $product->sku . ' | URL_KEY:' . $product->url_key . PHP_EOL;
    
    // Check inventories for master product
    echo 'MASTER_INVENTORIES:' . json_encode($product->inventories->toArray()) . PHP_EOL;
    
    // Check inventory indices
    $invIndices = DB::table('product_inventory_indices')->where('product_id', $product->id)->get();
    echo 'MASTER_INVENTORY_INDICES:' . json_encode($invIndices->toArray()) . PHP_EOL;
    
    // Check flat table
    $flat = DB::table('product_flat')->where('product_id', $product->id)->first();
    if ($flat) {
        echo 'FLAT_VISIBLE_INDIVIDUALLY:' . ($flat->visible_individually ?? 'N/A') . ' | FLAT_STATUS:' . ($flat->status ?? 'N/A') . PHP_EOL;
    }
    
    // If configurable product, check each variant
    if ($product->type === 'configurable') {
        echo 'VARIANTS_COUNT:' . $product->variants->count() . PHP_EOL;
        foreach ($product->variants as $var) {
            $varInvs = DB::table('product_inventories')->where('product_id', $var->id)->get();
            $varIndices = DB::table('product_inventory_indices')->where('product_id', $var->id)->get();
            $proj = DB::table('external_variant_projections')->where('variant_product_id', $var->id)->first();
            
            echo '  VARIANT_ID:' . $var->id . ' | SKU:' . $var->sku . ' | PROJ_EXT_SKU:' . ($proj ? $proj->external_sku_id : 'NONE') . PHP_EOL;
            echo '    INVENTORIES:' . json_encode($varInvs->toArray()) . PHP_EOL;
            echo '    INDICES:' . json_encode($varIndices->toArray()) . PHP_EOL;
        }
    }
    
    // Check payload snapshot
    if ($imp->payload_snapshot) {
        $snap = is_array($imp->payload_snapshot) ? $imp->payload_snapshot : json_decode($imp->payload_snapshot, true);
        if (isset($snap['variants'])) {
            echo 'SNAPSHOT_VARIANTS_STOCK:' . PHP_EOL;
            foreach ($snap['variants'] as $v) {
                echo '    SKU:' . ($v['sku_id'] ?? $v['skuId'] ?? 'N/A') . ' | STOCK:' . ($v['stock'] ?? 'N/A') . ' | PRICE:' . ($v['price'] ?? 'N/A') . PHP_EOL;
            }
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file('/home/highest-ye/htdocs/highest-ye.store/check_last_5_imports.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php check_last_5_imports.php && rm check_last_5_imports.php')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
