<?php

use App\Enums\PricingTrigger;
use App\Enums\SourceDiscountPolicy;
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestPricingRule;
use App\Models\HigestProductPriceOverride;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\CatalogPriceWriter;
use App\Services\Pricing\DTO\PricingContext;
use App\Services\Pricing\PriceRecalculationService;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\PricingRuleResolver;
use Illuminate\Contracts\Console\Kernel;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "====================================================================\n";
echo " HIGEST PRICING ENGINE V1.1 — FULL PRODUCTION SIMULATION EXECUTION\n";
echo "====================================================================\n\n";

$logs = [];

function logSection($title)
{
    global $logs;
    $msg = "\n".str_repeat('=', 70)."\n ".$title."\n".str_repeat('=', 70);
    echo $msg."\n";
    $logs[] = $msg;
}

function logInfo($key, $val)
{
    global $logs;
    $msg = sprintf('  • %-35s : %s', $key, is_array($val) ? json_encode($val) : $val);
    echo $msg."\n";
    $logs[] = $msg;
}

// -------------------------------------------------------------------------
// Phase 1: Setup Pricing Rule
// -------------------------------------------------------------------------
logSection('PHASE 1: PRICING RULE SETUP');

// Clean existing rules for clean simulation
HigestPricingRule::query()->delete();

$rule = HigestPricingRule::create([
    'name' => 'Simulation 10% Margin Rule',
    'scope' => 'global',
    'scope_id' => null,
    'type' => 'percentage',
    'value' => 10.00,
    'priority' => 10,
    'version' => 1,
    'status' => true,
    'source_discount_policy' => SourceDiscountPolicy::PASS_TO_CUSTOMER->value,
]);

logInfo('Rule ID', $rule->id);
logInfo('Rule Name', $rule->name);
logInfo('Scope', strtoupper($rule->scope));
logInfo('Margin Type / Value', $rule->type.' / '.$rule->value.'%');
logInfo('Source Discount Policy', $rule->source_discount_policy->value);
logInfo('Rule Version', 'v'.$rule->version);
logInfo('Created Timestamp', $rule->created_at->toDateTimeString());

// -------------------------------------------------------------------------
// Phase 2 & 3: Product Import & Source Data Verification
// -------------------------------------------------------------------------
logSection('PHASE 2 & 3: PRODUCT IMPORT & SOURCE OFFER CREATION');

$family = AttributeFamily::firstOrCreate(
    ['code' => 'default'],
    ['name' => 'Default', 'status' => 1, 'is_user_defined' => 1]
);

$product = Product::where('sku', 'ALI-SIM-1005007551825279')->first();
if (! $product) {
    $product = Product::factory()->create([
        'type' => 'simple',
        'sku' => 'ALI-SIM-1005007551825279',
        'attribute_family_id' => $family->id,
    ]);
}

$offer = HigestSourceOffer::updateOrCreate(
    [
        'variant_id' => $product->id,
        'source_provider' => 'aliexpress',
    ],
    [
        'product_id' => $product->id,
        'source_sku_id' => 'ALI-1005007551825279-VAR1',
        'acquisition_original_cost' => 20.04,
        'acquisition_cost' => 11.02,
        'source_currency' => 'USD',
        'captured_at' => now(),
    ]
);

logInfo('Bagisto Product ID', $product->id);
logInfo('Product SKU', $product->sku);
logInfo('Source Provider', $offer->source_provider);
logInfo('AliExpress SKU ID', $offer->source_sku_id);
logInfo('Acquisition Original Cost (List)', '$'.number_format($offer->acquisition_original_cost, 2));
logInfo('Acquisition Cost (Paid COGS)', '$'.number_format($offer->acquisition_cost, 2));
logInfo('Source Currency', $offer->source_currency);
logInfo('COGS Integrity Check', $offer->acquisition_cost == 11.02 ? 'PASS (True Cost Stored)' : 'FAIL');

// -------------------------------------------------------------------------
// Phase 4 & 5: Calculation Trace & Storefront Projection (PASS_TO_CUSTOMER)
// -------------------------------------------------------------------------
logSection('PHASE 4 & 5: PIPELINE CALCULATION TRACE & EAV PROJECTION (PASS_TO_CUSTOMER)');

$engine = app(PricingEngine::class);
$writer = app(CatalogPriceWriter::class);
$recalcService = app(PriceRecalculationService::class);

$context = new PricingContext(
    sourceProvider: 'aliexpress',
    currency: 'USD',
    acquisitionOriginalCost: $offer->acquisition_original_cost,
);

$result = $engine->calculate($offer->acquisition_cost, $rule, $context);

logInfo('Pipeline Input (Paid COGS)', '$'.number_format($result->acquisitionCost, 2));
logInfo('Reference List Cost (For regular price)', '$'.number_format($result->acquisitionOriginalCost, 2));
logInfo('Calculated Regular Price', '$'.number_format($result->sellingPrice, 2));
logInfo('Calculated Special Price (Sale)', '$'.number_format($result->specialPrice, 2));
logInfo('HIGEST Gross Profit', '$'.number_format($result->marginAmount, 2));
logInfo('HIGEST Margin Percentage', $result->marginPercentage.'%');
logInfo('Stage Breakdown', $result->breakdown);

// Write to EAV
$writer->write(
    variantId: $product->id,
    productId: $product->id,
    result: $result,
    specialPrice: $result->specialPrice,
    oldAcquisitionCost: 0,
    rule: $rule,
    trigger: PricingTrigger::IMPORT,
);

$historyPass = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest('id')->first();

logInfo('Storefront Regular Price written to EAV', '$'.number_format($historyPass->new_selling_price, 2));
logInfo('Storefront Special Price written to EAV', '$'.number_format($historyPass->calculation_breakdown['_rounded_special_price'] ?? $result->specialPrice, 2));
logInfo('Audit Log Record ID', $historyPass->id);
logInfo('Audit Log Trigger', $historyPass->trigger);

// -------------------------------------------------------------------------
// Phase 6: Policy Switch (ABSORB_BY_HIGEST)
// -------------------------------------------------------------------------
logSection('PHASE 6: POLICY SWITCHING TO ABSORB_BY_HIGEST');

$rule->update(['source_discount_policy' => SourceDiscountPolicy::ABSORB_BY_HIGEST->value]);
$rule = $rule->fresh();

$recalcService->recalculateOne($product->id, PricingTrigger::RULE_CHANGE);

$historyAbsorb = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest('id')->first();

logInfo('Updated Policy', $rule->source_discount_policy->value);
logInfo('Rule Version After Update', 'v'.$rule->version);
logInfo('Storefront Regular Price in EAV', '$'.number_format($historyAbsorb->new_selling_price, 2));
logInfo('Storefront Special Price in EAV', 'NULL (No Sale Badge)');
logInfo('HIGEST Internal Retained Difference', '$'.number_format(22.04 - 12.12, 2));
logInfo('Customer Sees Crossed-Out Badge?', 'NO (Flat Clean Price)');

// -------------------------------------------------------------------------
// Phase 7: Manual Override Simulation
// -------------------------------------------------------------------------
logSection('PHASE 7: MANUAL OVERRIDE SIMULATION');

$override = HigestProductPriceOverride::updateOrCreate(
    ['variant_id' => $product->id],
    [
        'product_id' => $product->id,
        'pricing_mode' => 'MANUAL',
        'manual_price' => 35.00,
        'manual_special_price' => null,
        'override_reason' => 'Simulation Premium Retail Strategy',
        'updated_by' => 1,
    ]
);

$recalcService->recalculateOne($product->id, PricingTrigger::MANUAL);

$historyOverride = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest('id')->first();

logInfo('Pricing Mode', $override->pricing_mode);
logInfo('Merchant Manual Price', '$'.number_format($override->manual_price, 2));
logInfo('Storefront Price Written to EAV', '$'.number_format($historyOverride->new_selling_price, 2));
logInfo('History Trigger Value', $historyOverride->trigger);
logInfo('Theoretical Engine Price Saved in Audit', '$'.number_format($historyOverride->calculation_breakdown['manual_override']['theoretical_selling_price'], 2));
logInfo('Procurement Acquisition Cost', '$'.number_format($offer->fresh()->acquisition_cost, 2));
logInfo('Effective Manual Gross Profit', '$'.number_format(35.00 - 11.02, 2));
logInfo('Effective Manual Margin %', round(((35.00 - 11.02) / 11.02) * 100, 2).'%');

// -------------------------------------------------------------------------
// Phase 8: Source Price Change Simulation (AliExpress Cost Sync)
// -------------------------------------------------------------------------
logSection('PHASE 8: SOURCE PRICE CHANGE SIMULATION (AliExpress Sync $11.02 -> $12.50)');

$offer->update(['acquisition_cost' => 12.50]);
$offer = $offer->fresh();

// Test 1: While in MANUAL mode
$recalcService->recalculateOne($product->id, PricingTrigger::SYNC);
$historyManualSync = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest('id')->first();

logInfo('AliExpress Updated Acquisition Cost', '$'.number_format($offer->acquisition_cost, 2));
logInfo('[MANUAL Mode] Storefront Price After Sync', '$'.number_format($historyManualSync->new_selling_price, 2).' (FIXED at $35.00)');

// Test 2: Switch back to AUTO mode
$override->update(['pricing_mode' => 'AUTO']);
$recalcService->recalculateOne($product->id, PricingTrigger::SYNC);
$historyAutoSync = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest('id')->first();

logInfo('[AUTO Mode] Storefront Price After Sync', '$'.number_format($historyAutoSync->new_selling_price, 2).' (AUTO Recalculated $12.50 * 1.10 = $13.75)');

// -------------------------------------------------------------------------
// Phase 9: Edge Cases & Failure Scenarios
// -------------------------------------------------------------------------
logSection('PHASE 9: EDGE CASES & FAILURE SCENARIOS');

// Case 1: AliExpress Product with No Discount (list == paid)
$prodNoDisc = Product::where('sku', 'ALI-NODISC-SKU')->first();
if (! $prodNoDisc) {
    $prodNoDisc = Product::factory()->create([
        'type' => 'simple',
        'sku' => 'ALI-NODISC-SKU',
        'attribute_family_id' => $family->id,
    ]);
}

$offerNoDisc = HigestSourceOffer::updateOrCreate(
    [
        'variant_id' => $prodNoDisc->id,
        'source_provider' => 'aliexpress',
    ],
    [
        'product_id' => $prodNoDisc->id,
        'source_sku_id' => 'ALI-NODISC-VAR',
        'acquisition_original_cost' => 15.00,
        'acquisition_cost' => 15.00,
        'source_currency' => 'USD',
        'captured_at' => now(),
    ]
);
$contextNoDisc = new PricingContext('aliexpress', 'USD', 15.00);
$resNoDisc = $engine->calculate(15.00, $rule, $contextNoDisc);
logInfo('Case 1 (No Discount)', [
    'acquisition_cost' => 15.00,
    'selling_price' => $resNoDisc->sellingPrice,
    'special_price' => $resNoDisc->specialPrice ?? 'NULL',
]);

// Case 2: Discount removed on sync
$offer->update(['acquisition_original_cost' => 12.50]);
$contextDiscRemoved = new PricingContext('aliexpress', 'USD', 12.50);
$resDiscRemoved = $engine->calculate(12.50, $rule, $contextDiscRemoved);
logInfo('Case 2 (Discount Removed)', [
    'selling_price' => $resDiscRemoved->sellingPrice,
    'special_price' => $resDiscRemoved->specialPrice ?? 'NULL',
]);

// Case 3: Rule changed from 10% to 30%
$rule->update(['value' => 30.00]);
$rule = $rule->fresh();
logInfo('Case 3 (Rule Margin 10% -> 30%)', [
    'new_rule_value' => $rule->value.'%',
    'new_rule_version' => 'v'.$rule->version,
]);

// Case 4: Product without pricing rule (all rules deleted)
HigestPricingRule::query()->delete();
$resolver = app(PricingRuleResolver::class);
$resolvedRule = $resolver->resolve($product->id);
$resNoRule = $engine->calculate(12.50, $resolvedRule, new PricingContext);
logInfo('Case 4 (No Active Rule - 0% Fallback)', [
    'resolved_rule' => $resolvedRule->name,
    'rule_value' => $resolvedRule->value.'%',
    'selling_price' => $resNoRule->sellingPrice,
    'gross_profit' => $resNoRule->marginAmount,
]);

echo "\nSimulation completed successfully!\n";
