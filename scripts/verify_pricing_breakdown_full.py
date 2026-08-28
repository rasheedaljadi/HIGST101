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
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\PricingRuleResolver;
use App\Services\Pricing\DTO\PricingContext;
use App\Enums\PricingTrigger;

$settings = AliExpressSetting::first();
$resolver = app(PricingRuleResolver::class);
$engine = app(PricingEngine::class);

echo "=========================================================\\n";
echo "1. VERIFYING NON-CHOICE PRODUCT (Product 316 - Has $5 Shipping):\\n";
echo "=========================================================\\n";
$import316 = AliExpressProductImport::where('product_id', 316)->first();
$offer317 = HigestSourceOffer::where('product_id', 316)->first();
$catId316 = $resolver->resolveCategoryId(316);
$rule316 = $resolver->resolve(316, $catId316);

$shippingCost316 = 0.0;
if ($settings->include_shipping_in_price && !($settings->exclude_choice_from_shipping_price && $import316->isChoice())) {
    $shippingCost316 = (float) $import316->base_shipping_cost;
}

$ctx316 = new PricingContext(
    sourceProvider: $offer317->source_provider,
    currency: $offer317->source_currency,
    acquisitionOriginalCost: $offer317->acquisition_original_cost !== null ? (float) $offer317->acquisition_original_cost : null,
    shippingCost: $shippingCost316,
);
$res316 = $engine->calculate((float) $offer317->acquisition_cost, $rule316, $ctx316);

echo "Product: {$import316->aliexpress_product_id} (Choice: " . ($import316->isChoice() ? 'YES' : 'NO') . ")\\n";
echo "Supplier Item Cost: $" . $offer317->acquisition_cost . "\\n";
echo "Supplier Shipping Cost: $" . $import316->base_shipping_cost . "\\n";
echo "Shipping Cost Added into Pricing Engine: $" . $shippingCost316 . " (INCLUDED: YES)\\n";
echo "Calculated Final Selling Price: $" . $res316->sellingPrice . "\\n";
echo "Calculation Breakdown: \\n";
foreach ($res316->breakdown as $stage => $data) {
    echo "  - {$stage}: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\\n";
}

echo "\\n=========================================================\\n";
echo "2. VERIFYING CHOICE PRODUCT (Product 1 - Has $5 Source Shipping):\\n";
echo "=========================================================\\n";
$import1 = AliExpressProductImport::where('product_id', 1)->first();
$offer1 = HigestSourceOffer::where('product_id', 1)->first();
$catId1 = $resolver->resolveCategoryId(1);
$rule1 = $resolver->resolve(1, $catId1);

$shippingCost1 = 0.0;
if ($settings->include_shipping_in_price && !($settings->exclude_choice_from_shipping_price && $import1->isChoice())) {
    $shippingCost1 = (float) $import1->base_shipping_cost;
}

$ctx1 = new PricingContext(
    sourceProvider: $offer1->source_provider,
    currency: $offer1->source_currency,
    acquisitionOriginalCost: $offer1->acquisition_original_cost !== null ? (float) $offer1->acquisition_original_cost : null,
    shippingCost: $shippingCost1,
);
$res1 = $engine->calculate((float) $offer1->acquisition_cost, $rule1, $ctx1);

echo "Product: {$import1->aliexpress_product_id} (Choice: " . ($import1->isChoice() ? 'YES' : 'NO') . ")\\n";
echo "Supplier Item Cost: $" . $offer1->acquisition_cost . "\\n";
echo "Supplier Shipping Cost on Source: $" . $import1->base_shipping_cost . "\\n";
echo "Shipping Cost Added into Pricing Engine: $" . $shippingCost1 . " (EXEMPTED: YES - ZERO SHIPPING ADDED)\\n";
echo "Calculated Final Selling Price: $" . $res1->sellingPrice . "\\n";
echo "Calculation Breakdown: \\n";
foreach ($res1->breakdown as $stage => $data) {
    echo "  - {$stage}: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\\n";
}
"""

with sftp.file("/home/highest-ye/htdocs/highest-ye.store/test_pricing_breakdown_full.php", "w") as f:
    f.write(php_script)
sftp.close()

code, out, err = r.run_remote_cmd(client, "cd /home/highest-ye/htdocs/highest-ye.store && php8.4 test_pricing_breakdown_full.php && rm test_pricing_breakdown_full.php")
print(f"CODE: {code}")
print(f"OUT:\n{out}")

client.close()
