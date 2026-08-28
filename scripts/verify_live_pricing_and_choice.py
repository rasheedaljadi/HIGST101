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
use App\Models\HigestPricingRule;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\PriceRecalculationService;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\PricingRuleResolver;
use App\Services\Pricing\CatalogPriceWriter;
use App\Services\Pricing\DTO\PricingContext;
use App\Enums\PricingTrigger;
use Illuminate\Support\Facades\DB;

$settings = AliExpressSetting::first();
echo "=========================================================\\n";
echo "1. CURRENT ALIEXPRESS SETTINGS:\\n";
echo "=========================================================\\n";
echo "Include Shipping in Price: " . ($settings->include_shipping_in_price ? 'ENABLED (True)' : 'DISABLED (False)') . "\\n";
echo "Exclude Choice from Shipping: " . ($settings->exclude_choice_from_shipping_price ? 'ENABLED (True)' : 'DISABLED (False)') . "\\n";
echo "Shipping Extra Days: " . $settings->shipping_extra_days . "\\n";

$pricingRule = HigestPricingRule::where('scope', 'global')->first();
$policy = is_object($pricingRule?->source_discount_policy) ? $pricingRule->source_discount_policy->value : $pricingRule?->source_discount_policy;
echo "Global Pricing Rule: " . ($pricingRule ? "{$pricingRule->name} ({$pricingRule->type}: {$pricingRule->value}%, Policy: {$policy})" : "None") . "\\n";

echo "\\n=========================================================\\n";
echo "2. SAMPLE NON-CHOICE PRODUCTS (WITH SHIPPING COST):\\n";
echo "=========================================================\\n";

$nonChoiceImports = AliExpressProductImport::whereNotNull('product_id')
    ->whereNotNull('base_shipping_cost')
    ->where('base_shipping_cost', '>', 0)
    ->get()
    ->filter(fn($imp) => ! $imp->isChoice())
    ->take(3);

foreach ($nonChoiceImports as $imp) {
    $flat = DB::table('product_flat')->where('product_id', $imp->product_id)->where('channel', 'default')->where('locale', 'ar')->first();
    $offer = HigestSourceOffer::where('product_id', $imp->product_id)->first();

    echo "Product ID: {$imp->product_id} | AliExpress ID: {$imp->aliexpress_product_id}\\n";
    echo "  - Title: " . ($flat?->name ?? 'N/A') . "\\n";
    echo "  - Is Choice: " . ($imp->isChoice() ? 'YES' : 'NO') . "\\n";
    echo "  - Base Shipping Cost: $" . $imp->base_shipping_cost . " " . $imp->shipping_currency . " (" . ($imp->shipping_company ?: 'Standard') . ")\\n";
    echo "  - Acquisition Item Cost: $" . ($offer?->acquisition_cost ?? 'N/A') . "\\n";
    echo "  - Total Base Cost with Shipping: $" . (($offer?->acquisition_cost ?? 0) + $imp->base_shipping_cost) . "\\n";
    echo "  - Selling Price in Store (Flat DB): $" . ($flat?->price ?? 'N/A') . "\\n";
    echo "  - Special Price in Store: $" . ($flat?->special_price ?? 'None') . "\\n";
    echo "\\n";
}

echo "=========================================================\\n";
echo "3. SAMPLE CHOICE PRODUCTS (EXEMPTED FROM SHIPPING COST):\\n";
echo "=========================================================\\n";

$choiceImports = AliExpressProductImport::whereNotNull('product_id')
    ->get()
    ->filter(fn($imp) => $imp->isChoice())
    ->take(3);

foreach ($choiceImports as $imp) {
    $flat = DB::table('product_flat')->where('product_id', $imp->product_id)->where('channel', 'default')->where('locale', 'ar')->first();
    $offer = HigestSourceOffer::where('product_id', $imp->product_id)->first();

    echo "Choice Product ID: {$imp->product_id} | AliExpress ID: {$imp->aliexpress_product_id}\\n";
    echo "  - Title: " . ($flat?->name ?? 'N/A') . "\\n";
    echo "  - Is Choice: YES\\n";
    echo "  - Source Shipping Cost: $" . ($imp->base_shipping_cost ?? 0) . "\\n";
    echo "  - Acquisition Item Cost: $" . ($offer?->acquisition_cost ?? 'N/A') . "\\n";
    echo "  - Selling Price in Store (Flat DB): $" . ($flat?->price ?? 'N/A') . "\\n";
    echo "  - Special Price in Store: $" . ($flat?->special_price ?? 'None') . "\\n";
    echo "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_pricing_verification.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_pricing_verification.php && rm test_pricing_verification.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
