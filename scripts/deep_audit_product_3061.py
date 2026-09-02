import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = r"""<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Comparing AliExpress Snapshot SKUs with Bagisto Variants for Product #3061 ===\n";

$import = DB::table('aliexpress_product_imports')->where('product_id', 3061)->first();
$payload = json_decode($import->payload_snapshot, true);

$aeVariants = $payload['variants'] ?? [];
echo "AliExpress Snapshot has " . count($aeVariants) . " variants:\n";
foreach ($aeVariants as $idx => $aev) {
    echo "  [AE #$idx] SKU ID: {$aev['sku_id']} | Price: \${$aev['price']} | Orig: \${$aev['original_price']} | Stock: {$aev['stock']} | Options: " . json_encode($aev['options_by_axis']) . "\n";
}

echo "\n=== External Variant Projections in Database ===\n";
$projections = DB::table('external_variant_projections')->where('product_id', 3061)->get();
foreach ($projections as $proj) {
    echo "  Projection: AE SKU {$proj->external_sku_id} -> Bagisto Variant #{$proj->variant_product_id}\n";
}

echo "\n=== Higest Source Offers in Database ===\n";
$offers = DB::table('higest_source_offers')->where('product_id', 3061)->get();
foreach ($offers as $off) {
    echo "  Offer: Variant #{$off->variant_id} (AE SKU: {$off->source_sku_id}) -> Acq Cost: \${$off->acquisition_cost}\n";
}

echo "\n=== Bagisto Variants Details ===\n";
$costAttr = DB::table('attributes')->where('code', 'cost')->first();
$priceAttr = DB::table('attributes')->where('code', 'price')->first();
$specialPriceAttr = DB::table('attributes')->where('code', 'special_price')->first();

$variants = DB::table('products')->where('parent_id', 3061)->get();
foreach ($variants as $v) {
    $cost = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $costAttr?->id)->value('float_value');
    $price = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $priceAttr?->id)->value('float_value');
    $special = DB::table('product_attribute_values')->where('product_id', $v->id)->where('attribute_id', $specialPriceAttr?->id)->value('float_value');
    
    // Get super attributes values for this variant
    $superVals = DB::table('product_attribute_values')
        ->join('attributes', 'product_attribute_values.attribute_id', '=', 'attributes.id')
        ->where('product_attribute_values.product_id', $v->id)
        ->whereIn('attributes.type', ['select'])
        ->select('attributes.code', 'product_attribute_values.integer_value')
        ->get();
    
    $opts = [];
    foreach ($superVals as $sv) {
        $optLabel = DB::table('attribute_options')->where('id', $sv->integer_value)->value('admin_name');
        $opts[$sv->code] = $optLabel ?: $sv->integer_value;
    }

    echo "  Bagisto Variant #{$v->id} ({$v->sku}): Cost=\${$cost}, Price=\${$price}, SpecialPrice=\${$special}, Options=" . json_encode($opts) . "\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/deep_audit_product_3061.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php deep_audit_product_3061.php && rm deep_audit_product_3061.php")
print(f"OUTPUT:\n{out}")
if err:
    print(f"ERR:\n{err}")

client.close()
