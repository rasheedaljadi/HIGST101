import remote_ssh_helper as r

client = r.get_ssh_client()

php_test = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\\Models\\AliExpressProductImport;
use App\\Models\\ExternalVariantProjection;
use Webkul\\Product\\Models\\Product;
use Webkul\\Product\\Models\\ProductFlat;
use Webkul\\Procurement\\Models\\ProcurementDemand;
use Webkul\\Product\\Type\\Configurable;

echo "=========================================================\\n";
echo "1. FINDING MP3 PRODUCT IN PRODUCT_FLAT & IMPORTS\\n";
echo "=========================================================\\n";

$flats = ProductFlat::where('name', 'like', '%MP3%')->get();
foreach ($flats as $f) {
    $p = Product::find($f->product_id);
    if (!$p) continue;
    echo "Product #{$p->id} (Type: {$p->type}, SKU: {$p->sku}): {$f->name}\\n";
    $import = AliExpressProductImport::where('product_id', $p->id)->first();
    if ($import) {
        echo "  Import #{$import->id}, AE ID: {$import->aliexpress_product_id}, Store: {$import->supplier_store_name}\\n";
    }
    if ($p->type === 'configurable') {
        $typeInstance = $p->getTypeInstance();
        $superAttrs = $p->super_attributes;
        echo "  Super Attributes count: " . $superAttrs->count() . "\\n";
        foreach ($superAttrs as $sa) {
            echo "    * SuperAttr: code={$sa->code}, id={$sa->id}\\n";
        }
        foreach ($p->variants as $v) {
            $vFlat = ProductFlat::where('product_id', $v->id)->first();
            $proj = ExternalVariantProjection::where('variant_product_id', $v->id)->first();
            $inv = $v->inventories->sum('qty');
            echo "    Variant #{$v->id} (SKU: {$v->sku}, Local Qty: {$inv}, Name: " . ($vFlat?->name ?? 'N/A') . "):\\n";
            if ($proj) {
                echo "      Proj: AE Prod {$proj->external_product_id}, AE SKU {$proj->external_sku_id}\\n";
            }
        }
    }
}

echo "\\n=========================================================\\n";
echo "2. CHECKING DEMANDS FOR MP3 PRODUCT\\n";
echo "=========================================================\\n";
$demands = ProcurementDemand::whereHas('orderItem', function($q) {
    $q->where('name', 'like', '%MP3%');
})->get();

foreach ($demands as $d) {
    echo "Demand #{$d->id} (Order #{$d->order_id}, State: {$d->state}):\\n";
    echo "  Product ID: {$d->product_id}, Supplier SKU: {$d->supplier_sku_id}\\n";
    echo "  OrderItem Additional: " . json_encode($d->orderItem?->additional) . "\\n";
}
"""

sftp = client.open_sftp()
with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_mp3_product2.php", "w") as f:
    f.write(php_test)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_mp3_product2.php && rm inspect_mp3_product2.php")
print(f"OUT:\n{out}")

client.close()
