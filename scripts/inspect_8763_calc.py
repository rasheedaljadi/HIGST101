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
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\PricingRuleResolver;
use App\Services\Pricing\DTO\PricingContext;
use App\Services\Pricing\PriceRecalculationService;
use App\Enums\PricingTrigger;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductFlat;
use Illuminate\Support\Facades\DB;

$productId = 8763;
$import = AliExpressProductImport::where('product_id', $productId)->first();
$offer = HigestSourceOffer::where('product_id', $productId)->first();
$settings = AliExpressSetting::first();
$resolver = app(PricingRuleResolver::class);
$engine = app(PricingEngine::class);
$rule = $resolver->resolve($productId, $resolver->resolveCategoryId($productId));

echo "=========================================================\\n";
echo "PRODUCT 8763 (Feelworld Monitor):\\n";
echo "=========================================================\\n";
echo "Supplier Acquisition Cost: $" . ($offer?->acquisition_cost ?? 'None') . "\\n";
echo "Supplier Acquisition Original (List) Cost: $" . ($offer?->acquisition_original_cost ?? 'None') . "\\n";
echo "Supplier Base Shipping Cost: $" . ($import?->base_shipping_cost ?? 'None') . "\\n";
echo "Settings in DB: include_shipping_in_price = " . ($settings->include_shipping_in_price ? 'true' : 'false') . "\\n";

// 1. Calculation WITH Shipping (include_shipping = true)
$ctxWithShipping = new PricingContext(
    sourceProvider: $offer->source_provider,
    currency: $offer->source_currency,
    acquisitionOriginalCost: (float) $offer->acquisition_original_cost,
    shippingCost: (float) $import->base_shipping_cost,
);
$resWithShipping = $engine->calculate((float) $offer->acquisition_cost, $rule, $ctxWithShipping);
echo "\\n--- If include_shipping_in_price = TRUE (WITH $29.32 shipping) ---\\n";
echo "  Regular Selling Price: $" . $resWithShipping->sellingPrice . "\\n";
echo "  Special Selling Price: $" . $resWithShipping->specialPrice . "\\n";

// 2. Calculation WITHOUT Shipping (include_shipping = false)
$ctxWithoutShipping = new PricingContext(
    sourceProvider: $offer->source_provider,
    currency: $offer->source_currency,
    acquisitionOriginalCost: (float) $offer->acquisition_original_cost,
    shippingCost: 0.0,
);
$resWithoutShipping = $engine->calculate((float) $offer->acquisition_cost, $rule, $ctxWithoutShipping);
echo "\\n--- If include_shipping_in_price = FALSE (WITHOUT shipping) ---\\n";
echo "  Regular Selling Price: $" . $resWithoutShipping->sellingPrice . "\\n";
echo "  Special Selling Price: $" . $resWithoutShipping->specialPrice . "\\n";

$flat = ProductFlat::where('product_id', $productId)->first();
echo "\\n--- Current Live Flat DB Price ---\\n";
echo "  Current Regular Price in DB: $" . $flat->price . "\\n";
echo "  Current Special Price in DB: $" . $flat->special_price . "\\n";
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/inspect_8763_calc.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 inspect_8763_calc.php && rm inspect_8763_calc.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
