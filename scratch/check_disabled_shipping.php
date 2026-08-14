<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$setting = \App\Models\AliExpressSetting::current();
echo "===========================================\n";
echo "Current Setting (include_shipping_in_price): " . ($setting->include_shipping_in_price ? 'ENABLED (true)' : 'DISABLED (false)') . "\n";
echo "===========================================\n\n";

if ($setting->include_shipping_in_price) {
    echo "Setting is currently ENABLED in DB. If the user saved with unchecked, it should be false. Let's make sure.\n";
}

echo "Recalculating prices across catalog with current setting...\n";
$service = app(\App\Services\Pricing\PriceRecalculationService::class);
$count = $service->recalculateAll(\App\Enums\PricingTrigger::MANUAL);
echo "Recalculated {$count} offers.\n\n";

$productIds = [1, 44, 657, 658];

foreach ($productIds as $pid) {
    $bagistoProduct = \Webkul\Product\Models\Product::find($pid);
    $aeImport = \App\Models\AliExpressProductImport::where('product_id', $pid)->first();
    $sourceOffer = \App\Models\HigestSourceOffer::where('product_id', $pid)->first();
    $history = \App\Models\HigestCalculatedPriceHistory::where('product_id', $pid)->latest()->first();

    echo "Product ID: {$pid}\n";
    echo "  - Supplier Item Cost: " . ($sourceOffer ? $sourceOffer->acquisition_cost : 'N/A') . "\n";
    echo "  - Supplier Stored Shipping: " . ($aeImport ? $aeImport->base_shipping_cost . ' ' . $aeImport->shipping_currency : 'N/A') . "\n";
    echo "  - Current Catalog Price (Selling Price): " . ($bagistoProduct ? $bagistoProduct->price : 'N/A') . "\n";
    if ($history) {
        $freight = $history->breakdown_json['freight_adjustment'] ?? null;
        echo "  - Freight in Calculation: " . json_encode($freight, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "-------------------------------------------\n";
}
