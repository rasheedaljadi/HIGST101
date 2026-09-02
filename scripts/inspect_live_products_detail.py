import remote_ssh_helper as r

client = r.get_ssh_client()
sftp = client.open_sftp()

php_script = """<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);
$kernel->bootstrap();

use App\Models\AliExpressProductImport;
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestSourceOffer;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;

foreach ([316, 329, 500] as $pid) {
    echo "=========================================================\\n";
    echo "PRODUCT ID: {$pid}\\n";
    echo "=========================================================\\n";
    $product = Product::with(['variants'])->find($pid);
    $imp = AliExpressProductImport::where('product_id', $pid)->first();
    echo "Type: " . ($product?->type ?? 'N/A') . "\\n";
    echo "Base Shipping Cost: " . ($imp?->base_shipping_cost ?? 'N/A') . "\\n";
    echo "Shipping Company: " . ($imp?->shipping_company ?? 'N/A') . "\\n";
    echo "isChoice: " . ($imp?->isChoice() ? 'YES' : 'NO') . "\\n";

    $history = HigestCalculatedPriceHistory::where('product_id', $pid)->orderByDesc('id')->take(3)->get();
    echo "\\nRecent Price Calculation History:\\n";
    foreach ($history as $h) {
        echo "  - Variant ID: {$h->variant_id} | AcqCost: {$h->acquisition_cost} | BreakDown: " . json_encode($h->calculation_breakdown, JSON_UNESCAPED_UNICODE) . " | Selling Price: {$h->selling_price}\\n";
    }

    if ($product && $product->type === 'configurable') {
        echo "\\nVariants:\\n";
        foreach ($product->variants as $v) {
            $vFlat = DB::table('product_flat')->where('product_id', $v->id)->where('channel', 'default')->where('locale', 'ar')->first();
            $vOffer = HigestSourceOffer::where('variant_id', $v->id)->first();
            echo "  - Variant {$v->id} (SKU: {$v->sku}): Offer Cost: {$vOffer?->acquisition_cost}, Price in Flat DB: {$vFlat?->price}\\n";
        }
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_prod_pricing_details.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_prod_pricing_details.php && rm inspect_prod_pricing_details.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
