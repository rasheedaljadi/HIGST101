import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('76.13.79.242', username='highest-ye', password='YoK2PBV1fo82yujX2tDq')

sftp = client.open_sftp()

# 1. Upload updated files
local_remote_pairs = [
    ('e:\\HIGESTO NEW1\\higest\\higest101\\app\\Services\\AliExpress\\AliExpressProductImporter.php', '/home/highest-ye/htdocs/highest-ye.store/app/Services/AliExpress/AliExpressProductImporter.php'),
    ('e:\\HIGESTO NEW1\\higest\\higest101\\packages\\Webkul\\Fulfillment\\src\\Listeners\\AliExpressStockListener.php', '/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Fulfillment/src/Listeners/AliExpressStockListener.php'),
    ('e:\\HIGESTO NEW1\\higest\\higest101\\packages\\Webkul\\Inventory\\src\\Database\\Seeders\\InventorySourcesModelV12Seeder.php', '/home/highest-ye/htdocs/highest-ye.store/packages/Webkul/Inventory/src/Database/Seeders/InventorySourcesModelV12Seeder.php'),
]

for local, remote in local_remote_pairs:
    print(f"Uploading {local} -> {remote}")
    sftp.put(local, remote)

# 2. Upload migration script to transfer inventory records
php_migration = """<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Core\Models\Channel;
use Webkul\Product\Helpers\Indexers\Inventory as InventoryIndexer;

echo "=== STEP 1: CONFIGURE INVENTORY SOURCES ===" . PHP_EOL;

$defaultSource = InventorySource::where('code', 'default')->first();
$aeSource = InventorySource::where('code', 'aliexpress_source')->first();

if (! $aeSource) {
    echo "Creating aliexpress_source..." . PHP_EOL;
    $aeSourceId = DB::table('inventory_sources')->insertGetId([
        'code' => 'aliexpress_source',
        'name' => 'مصدر كتالوج علي إكسبرس الافتراضي',
        'description' => 'مصدر إسقاط افتراضي لعرض منتجات دروبشوبنج. غير قابل للبيع المباشر كمخزون مادي محلي.',
        'contact_name' => 'سحابة تكامل علي إكسبرس',
        'contact_email' => 'integration@aliexpress.hayest.com',
        'contact_number' => '+86-00-000000',
        'country' => 'CN',
        'state' => 'GLOBAL',
        'city' => 'منصة سحابية',
        'street' => 'بوابة AliExpress API',
        'postcode' => '00000',
        'status' => 1,
        'is_salable' => 1,
        'is_delivery_source' => 0,
        'source_type' => 'virtual_projection',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $aeSource = InventorySource::find($aeSourceId);
} else {
    $aeSource->update([
        'status' => 1,
        'is_salable' => 1,
        'is_delivery_source' => 0,
        'source_type' => 'virtual_projection',
    ]);
}

echo "aliexpress_source ID: {$aeSource->id} | is_salable: {$aeSource->is_salable}" . PHP_EOL;

// Link aliexpress_source and default to channel 1
$channel = Channel::first();
if ($channel) {
    $allSourcesToLink = InventorySource::whereIn('code', [
        'default', 'aliexpress_source', 'hayest_central', 'hayest_dropship_ye', 'hayest_internal_ye'
    ])->pluck('id');
    
    foreach ($allSourcesToLink as $sId) {
        DB::table('channel_inventory_sources')->updateOrInsert(
            ['channel_id' => $channel->id, 'inventory_source_id' => $sId]
        );
    }
}

echo "=== STEP 2: TRANSFER ALIEXPRESS INVENTORIES TO ALIEXPRESS_SOURCE ===" . PHP_EOL;

// Find all AliExpress product IDs and variant IDs
$aliExpressImportsProductIds = DB::table('aliexpress_product_imports')->pluck('product_id')->filter()->toArray();
$aliExpressVariantIds = DB::table('products')->whereIn('parent_id', $aliExpressImportsProductIds)->pluck('id')->toArray();
$aeProductAndVariantIds = array_unique(array_merge($aliExpressImportsProductIds, $aliExpressVariantIds));

echo "Total AliExpress Product & Variant IDs: " . count($aeProductAndVariantIds) . PHP_EOL;

// Move product_inventories from default (id 1) to aliexpress_source (id 3)
$affected = DB::table('product_inventories')
    ->where('inventory_source_id', $defaultSource->id)
    ->whereIn('product_id', $aeProductAndVariantIds)
    ->update(['inventory_source_id' => $aeSource->id]);

echo "Transferred {$affected} inventory records from default to aliexpress_source." . PHP_EOL;

$defaultCount = DB::table('product_inventories')->where('inventory_source_id', $defaultSource->id)->count();
$aeCount = DB::table('product_inventories')->where('inventory_source_id', $aeSource->id)->count();
$aeTotalQty = DB::table('product_inventories')->where('inventory_source_id', $aeSource->id)->sum('qty');

echo "DEFAULT_WAREHOUSE_REMAINING_ROWS: {$defaultCount}" . PHP_EOL;
echo "ALIEXPRESS_SOURCE_ROWS: {$aeCount} | TOTAL_QTY: {$aeTotalQty}" . PHP_EOL;

echo "=== STEP 3: RUN FULL INVENTORY REINDEX ===" . PHP_EOL;
$indexer = app(InventoryIndexer::class);
$indexer->reindexFull();

echo "=== STEP 4: VERIFY SAMPLE PRODUCTS ===" . PHP_EOL;
$sampleAe = DB::table('products')->where('sku', 'like', 'ae-%')->take(5)->get();
foreach ($sampleAe as $p) {
    $invs = DB::table('product_inventories')->where('product_id', $p->id)->get();
    $indices = DB::table('product_inventory_indices')->where('product_id', $p->id)->get();
    echo "PRODUCT: {$p->sku} (ID: {$p->id}) | INVENTORIES: " . json_encode($invs->toArray()) . " | INDICES: " . json_encode($indices->toArray()) . PHP_EOL;
}
"""

with sftp.file('/home/highest-ye/htdocs/highest-ye.store/migrate_to_ae_source.php', 'w') as f:
    f.write(php_migration)
sftp.close()

stdin, stdout, stderr = client.exec_command('cd /home/highest-ye/htdocs/highest-ye.store && php migrate_to_ae_source.php && rm migrate_to_ae_source.php && php artisan optimize:clear')
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
client.close()
