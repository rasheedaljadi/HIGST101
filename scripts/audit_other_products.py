import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Auditing Product #8740 (Ring) and its Variants ===\n";

$costAttr = DB::table('attributes')->where('code', 'cost')->first();
$priceAttr = DB::table('attributes')->where('code', 'price')->first();
$specialPriceAttr = DB::table('attributes')->where('code', 'special_price')->first();

$parent = DB::table('products')->where('id', 8740)->first();
$parentCost = DB::table('product_attribute_values')->where('product_id', 8740)->where('attribute_id', $costAttr?->id)->value('float_value');
echo "Parent #8740 Cost: \${$parentCost}\n";

$variants = DB::table('products')->where('parent_id', 8740)->get();
echo "Found " . $variants->count() . " variants for #8740:\n";
foreach ($variants as $v) {
    $cost = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $costAttr?->id)->value('float_value');
    $price = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $priceAttr?->id)->value('float_value');
    $special = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $specialPriceAttr?->id)->value('float_value');
    echo "  Variant #{$v->id} ({$v->sku}): Cost=\${$cost}, Price=\${$price}, SpecialPrice=\${$special}\n";
}

echo "\n=== Auditing all Configurable Products with multiple variant costs ===\n";
$configurables = DB::table('products')->where('type', 'configurable')->limit(5)->get();
foreach ($configurables as $cp) {
    $vCosts = DB::table('products')
        ->join('product_attribute_values', 'products.id', '=', 'product_attribute_values.product_id')
        ->where('products.parent_id', $cp->id)
        ->where('product_attribute_values.attribute_id', $costAttr?->id)
        ->pluck('product_attribute_values.float_value')
        ->unique()
        ->values()
        ->all();
    
    echo "Configurable Product #{$cp->id} ({$cp->sku}) -> Distinct Variant Costs: " . json_encode($vCosts) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/audit_other_products.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php audit_other_products.php && rm audit_other_products.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
