import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;

$variant = Product::find(9136);
echo "Variant 9136 parent_id: " . ($variant?->parent_id ?? 'NULL') . "\\n";

$offer = HigestSourceOffer::where('variant_id', 9136)->first();
echo "Offer 9136 product_id: " . ($offer?->product_id ?? 'NULL') . "\\n";
echo "Offer 9136 variant_id: " . ($offer?->variant_id ?? 'NULL') . "\\n";

$impByOfferProdId = AliExpressProductImport::where('product_id', $offer->product_id)->first();
echo "Import by offer->product_id ({$offer->product_id}): " . ($impByOfferProdId ? "FOUND (ID: {$impByOfferProdId->id}, base_shipping: {$impByOfferProdId->base_shipping_cost})" : "NOT FOUND") . "\\n";

$parent = Product::find($variant->parent_id);
echo "Parent ID: " . ($parent?->id ?? 'NULL') . "\\n";
$impByParentId = AliExpressProductImport::where('product_id', $variant->parent_id)->first();
echo "Import by variant->parent_id ({$variant->parent_id}): " . ($impByParentId ? "FOUND (ID: {$impByParentId->id}, base_shipping: {$impByParentId->base_shipping_cost})" : "NOT FOUND") . "\\n";

// Check AliExpressProductImporter on remote
$importerFile = file_get_contents(__DIR__ . '/app/Services/AliExpress/AliExpressProductImporter.php');
echo "Importer contains base_shipping_cost check: " . (str_contains($importerFile, 'base_shipping_cost') ? "YES" : "NO") . "\\n";

// Check PriceRecalculationService on remote
$recalcFile = file_get_contents(__DIR__ . '/app/Services/Pricing/PriceRecalculationService.php');
echo "RecalcService contains base_shipping_cost check: " . (str_contains($recalcFile, 'base_shipping_cost') ? "YES" : "NO") . "\\n";

// Check when the last history for 9136 was created
$hist = DB::table('higest_calculated_price_histories')->where('variant_id', 9136)->orderByDesc('id')->first();
echo "Last history created_at for 9136: " . ($hist?->created_at ?? 'NULL') . "\\n";
echo "Import created_at / updated_at: {$impByOfferProdId?->created_at} / {$impByOfferProdId?->updated_at}\\n";
echo "Import shipping_synced_at: {$impByOfferProdId?->shipping_synced_at}\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/debug_9136_shipping.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 debug_9136_shipping.php && rm debug_9136_shipping.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
