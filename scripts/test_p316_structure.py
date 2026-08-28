import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\AliExpressSetting;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\PriceRecalculationService;
use App\Enums\PricingTrigger;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

$p316 = Product::with('variants')->find(316);
echo "Product 316 Type: {$p316->type} | SKU: {$p316->sku}\\n";
echo "Variants count: " . $p316->variants->count() . "\\n";

$offers = HigestSourceOffer::where('product_id', 316)->get();
echo "Offers count for product 316: " . $offers->count() . "\\n";
foreach ($offers->take(3) as $off) {
    echo "  Offer ID: {$off->id}, Variant ID: {$off->variant_id}, Acq Cost: {$off->acquisition_cost}\\n";
}

$recalculator = app(PriceRecalculationService::class);
if ($offers->isNotEmpty()) {
    $firstVariantId = $offers->first()->variant_id;
    echo "\\nRecalculating variant ID {$firstVariantId}...\\n";
    $res = $recalculator->recalculateOne($firstVariantId, PricingTrigger::MANUAL);
    echo "Result selling price: " . var_export($res, true) . "\\n";

    $flat = DB::table('product_flat')->where('product_id', $firstVariantId)->first();
    echo "Variant flat price: " . ($flat?->price ?? 'None') . "\\n";

    $parentFlat = DB::table('product_flat')->where('product_id', 316)->first();
    echo "Parent product 316 flat price: " . ($parentFlat?->price ?? 'None') . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_p316_structure.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_p316_structure.php && rm test_p316_structure.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
