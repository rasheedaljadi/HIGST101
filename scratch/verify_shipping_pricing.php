<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$resolver = app(\App\Services\Pricing\PricingRuleResolver::class);
$engine = app(\App\Services\Pricing\PricingEngine::class);

foreach ([1, 44, 657, 658] as $pid) {
    $offer = \App\Models\HigestSourceOffer::where('product_id', $pid)->first();
    $ae = \App\Models\AliExpressProductImport::where('product_id', $pid)->first();
    $catId = $resolver->resolveCategoryId($pid);
    $rule = $resolver->resolve($pid, $catId);

    $context = new \App\Services\Pricing\DTO\PricingContext(
        sourceProvider: 'aliexpress',
        currency: 'USD',
        acquisitionOriginalCost: $offer?->acquisition_original_cost !== null ? (float) $offer->acquisition_original_cost : null,
        shippingCost: (float) ($ae?->base_shipping_cost ?? 0.0),
    );

    $result = $rule ? $engine->calculate((float) $offer->acquisition_cost, $rule, $context) : null;

    echo "Product ID: {$pid}\n";
    echo "  - Acquisition Cost: " . ($offer?->acquisition_cost ?? 'N/A') . "\n";
    echo "  - Shipping Cost: " . ($ae?->base_shipping_cost ?? 'N/A') . "\n";
    echo "  - Rule: " . ($rule ? "ID: {$rule->id}, Name: {$rule->name}, Margin: {$rule->margin_value} ({$rule->margin_type})" : 'NO RULE MATCHED') . "\n";
    if ($result) {
        echo "  - Result Selling Price: {$result->sellingPrice}\n";
        echo "  - Result Special Price: {$result->specialPrice}\n";
        echo "  - Breakdown: " . json_encode($result->breakdown, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "-------------------------------------------\n";
}
