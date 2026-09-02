import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

echo "=== 1. Checking Product #3065 ===\n";
$p = DB::table('products')->where('id', 3065)->first();
print_r($p);

echo "\n=== 2. Is 3065 a parent or a variant? ===\n";
if ($p->parent_id) {
    echo "Product 3065 is a VARIANT of parent #{$p->parent_id}\n";
    $parentId = $p->parent_id;
} else {
    echo "Product 3065 is a PARENT product (or simple product)\n";
    $parentId = 3065;
}

echo "\n=== 3. All variants for Parent #{$parentId} ===\n";
$variants = DB::table('products')->where('parent_id', $parentId)->get();
echo "Found " . $variants->count() . " variants:\n";

$costAttr = DB::table('attributes')->where('code', 'cost')->first();
$priceAttr = DB::table('attributes')->where('code', 'price')->first();
$specialPriceAttr = DB::table('attributes')->where('code', 'special_price')->first();

foreach ($variants as $v) {
    $costVal = DB::table('product_attribute_values')
        ->where('product_id', $v->id)
        ->where('attribute_id', $costAttr?->id)
        ->value('float_value');

    $priceVal = DB::table('product_attribute_values')
        ->where('product_id', $v->id)
        ->where('attribute_id', $priceAttr?->id)
        ->value('float_value');

    $specialVal = DB::table('product_attribute_values')
        ->where('product_id', $v->id)
        ->where('attribute_id', $specialPriceAttr?->id)
        ->value('float_value');

    $flat = DB::table('product_flat')->where('product_id', $v->id)->first();

    echo "Variant #{$v->id} (SKU: {$v->sku}):\n";
    echo "  - Cost Attribute (PAV): \${$costVal}\n";
    echo "  - Price Attribute (PAV): \${$priceVal}\n";
    echo "  - Special Price (PAV): \${$specialVal}\n";
    if ($flat) {
        echo "  - Flat Price: \${$flat->price}, Flat Special Price: \${$flat->special_price}\n";
    }
}

echo "\n=== 4. Checking Parent #{$parentId} attribute values ===\n";
$parentCost = DB::table('product_attribute_values')
    ->where('product_id', $parentId)
    ->where('attribute_id', $costAttr?->id)
    ->value('float_value');
$parentPrice = DB::table('product_attribute_values')
    ->where('product_id', $parentId)
    ->where('attribute_id', $priceAttr?->id)
    ->value('float_value');
echo "Parent #{$parentId} Cost: \${$parentCost}, Price: \${$parentPrice}\n";

echo "\n=== 5. Checking aliexpress_product_imports for Product #{$parentId} ===\n";
$import = DB::table('aliexpress_product_imports')
    ->where('product_id', $parentId)
    ->orWhere('aliexpress_product_id', '1005012027191536')
    ->first();

if ($import) {
    echo "Import ID: {$import->id}, AE Product ID: {$import->aliexpress_product_id}\n";
    $payload = json_decode($import->payload_snapshot, true);
    if (isset($payload['ae_item_base_info_dto'])) {
        echo "Found ae_item_base_info_dto in payload\n";
    }
    if (isset($payload['ae_item_sku_info_dt_os_list'])) {
        $skuList = $payload['ae_item_sku_info_dt_os_list']['ae_item_sku_info_d_t_o'] ?? [];
        echo "AliExpress SKUs in payload snapshot (" . count($skuList) . " SKUs):\n";
        foreach ($skuList as $sku) {
            echo "  - SKU ID: " . ($sku['sku_id'] ?? 'N/A') . 
                 " | sku_attr: " . ($sku['sku_attr'] ?? 'N/A') . 
                 " | offer_sale_price (cost): " . ($sku['offer_sale_price'] ?? ($sku['sku_price'] ?? 'N/A')) . 
                 " | sku_stock: " . ($sku['sku_available_stock'] ?? ($sku['ipm_sku_stock'] ?? 'N/A')) . "\n";
        }
    }
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/audit_product_variants.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php audit_product_variants.php && rm audit_product_variants.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
