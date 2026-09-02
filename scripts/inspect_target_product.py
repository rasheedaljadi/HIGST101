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
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestPricingRule;
use App\Models\HigestSourceOffer;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductFlat;
use Illuminate\Support\Facades\DB;

$aeId = '1005011969479212';
$urlSlug = 'ae-1005011969479212-variant-1466';

echo "=========================================================\\n";
echo "SEARCHING FOR PRODUCT: {$urlSlug} / AE ID: {$aeId}\\n";
echo "=========================================================\\n";

$settings = AliExpressSetting::first();
echo "Include Shipping: " . ($settings->include_shipping_in_price ? 'YES' : 'NO') . "\\n";
echo "Exclude Choice: " . ($settings->exclude_choice_from_shipping_price ? 'YES' : 'NO') . "\\n";

$import = AliExpressProductImport::where('aliexpress_product_id', $aeId)->first();
if ($import) {
    echo "\\n[Import Record]\\n";
    echo "  - Import ID: {$import->id}\\n";
    echo "  - Linked Product ID: {$import->product_id}\\n";
    echo "  - Status: {$import->status}\\n";
    echo "  - Base Shipping Cost: $" . ($import->base_shipping_cost ?? 'None') . " " . $import->shipping_currency . "\\n";
    echo "  - Shipping Company: " . ($import->shipping_company ?: 'None') . "\\n";
    echo "  - isChoice: " . ($import->isChoice() ? 'YES (Exempt from shipping)' : 'NO (Shipping must be included)') . "\\n";
    echo "  - Payload snapshot keys: " . json_encode(array_keys(is_array($import->payload_snapshot) ? $import->payload_snapshot : [])) . "\\n";
    if (isset($import->payload_snapshot['shipping'])) {
        echo "  - Shipping Snapshot: " . json_encode($import->payload_snapshot['shipping'], JSON_UNESCAPED_UNICODE) . "\\n";
    }
} else {
    echo "No import record found for AE ID {$aeId}\\n";
}

// Find by URL key or SKU or ID
$flat = DB::table('product_flat')
    ->where('url_key', $urlSlug)
    ->orWhere('url_key', 'like', "%{$aeId}%")
    ->orWhere('product_id', $import?->product_id)
    ->get();

echo "\\n[Product Flat Records (Count: " . $flat->count() . ")]\\n";
foreach ($flat as $f) {
    echo "  - Product ID: {$f->product_id} | Channel: {$f->channel} | Locale: {$f->locale}\\n";
    echo "    Title: {$f->name}\\n";
    echo "    URL Key: {$f->url_key}\\n";
    echo "    Regular Price: $" . $f->price . "\\n";
    echo "    Special Price: " . ($f->special_price !== null ? '$' . $f->special_price : 'None') . "\\n";
}

$targetProductId = $import?->product_id ?? ($flat->first()?->product_id);
if ($targetProductId) {
    $product = Product::with(['variants'])->find($targetProductId);
    echo "\\n[Product Model Details]\\n";
    echo "  - Type: " . ($product?->type ?? 'N/A') . "\\n";
    echo "  - SKU: " . ($product?->sku ?? 'N/A') . "\\n";

    $offers = HigestSourceOffer::where('product_id', $targetProductId)
        ->orWhere('variant_id', $targetProductId)
        ->get();
    
    echo "\\n[Source Offers (Count: " . $offers->count() . ")]\\n";
    foreach ($offers as $off) {
        echo "  - Variant ID: {$off->variant_id} | SKU ID: {$off->source_sku_id}\\n";
        echo "    Acquisition Cost: $" . $off->acquisition_cost . " " . $off->source_currency . "\\n";
        echo "    Original Cost: $" . ($off->acquisition_original_cost ?? 'None') . "\\n";
    }

    $history = HigestCalculatedPriceHistory::where('product_id', $targetProductId)
        ->orderByDesc('id')
        ->take(5)
        ->get();

    echo "\\n[Price Calculation Histories]\\n";
    foreach ($history as $h) {
        echo "  - Variant ID: {$h->variant_id} | Rule ID: {$h->pricing_rule_id}\\n";
        echo "    Acquisition Cost: $" . $h->acquisition_cost . "\\n";
        echo "    Selling Price: $" . $h->selling_price . "\\n";
        echo "    Special Price: " . ($h->special_price !== null ? '$' . $h->special_price : 'None') . "\\n";
        echo "    Breakdown: " . json_encode($h->calculation_breakdown, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\\n";
    }

    if ($product && $product->variants->isNotEmpty()) {
        echo "\\n[Variants in Product]\\n";
        foreach ($product->variants as $v) {
            $vFlat = DB::table('product_flat')->where('product_id', $v->id)->where('locale', 'ar')->first();
            $vOffer = HigestSourceOffer::where('variant_id', $v->id)->first();
            $vHist = HigestCalculatedPriceHistory::where('variant_id', $v->id)->orderByDesc('id')->first();
            echo "  - Variant {$v->id} (SKU: {$v->sku})\\n";
            echo "    Offer Cost: $" . ($vOffer?->acquisition_cost ?? 'N/A') . " (Orig: $" . ($vOffer?->acquisition_original_cost ?? 'N/A') . ")\\n";
            echo "    Flat DB Price: $" . ($vFlat?->price ?? 'N/A') . " | Special: " . ($vFlat?->special_price !== null ? '$' . $vFlat->special_price : 'None') . "\\n";
            if ($vHist) {
                $b = $vHist->calculation_breakdown;
                $shipping = $b['freight_adjustment']['shipping_cost'] ?? 'N/A';
                $margin = $b['margin_calculation']['added_margin'] ?? 'N/A';
                echo "    History Breakdown -> Shipping: $" . $shipping . " | Added Margin: $" . $margin . " | Final Special: $" . ($b['rounding']['final_special_price'] ?? 'None') . "\\n";
            }
        }
    }
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_target_product.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_target_product.php && rm inspect_target_product.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
