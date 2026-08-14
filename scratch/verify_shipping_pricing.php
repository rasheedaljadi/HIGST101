<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$setting = \App\Models\AliExpressSetting::current();
echo "===========================================\n";
echo "AliExpress Setting include_shipping_in_price: " . ($setting->include_shipping_in_price ? 'ENABLED (true)' : 'DISABLED (false)') . "\n";
echo "===========================================\n";

echo "Running recalculateAll() with shipping included...\n";
$service = app(\App\Services\Pricing\PriceRecalculationService::class);
$count = $service->recalculateAll(\App\Enums\PricingTrigger::MANUAL);
echo "Recalculated prices for {$count} source offers.\n\n";

$products = \App\Models\AliExpressProductImport::whereNotNull('product_id')->take(8)->get();

foreach ($products as $p) {
    $bagistoProduct = \Webkul\Product\Models\Product::find($p->product_id);
    $sourceOffer = \App\Models\HigestSourceOffer::where('product_id', $p->product_id)->first();
    $history = \App\Models\HigestCalculatedPriceHistory::where('product_id', $p->product_id)->latest()->first();

    echo "Product ID: {$p->product_id} | AE ID: {$p->aliexpress_product_id}\n";
    echo "  - Stored Shipping Cost (AliExpress): " . ($p->base_shipping_cost !== null ? $p->base_shipping_cost . ' ' . $p->shipping_currency : 'None/Not Synced') . "\n";
    echo "  - Supplier Cost: " . ($sourceOffer ? $sourceOffer->acquisition_cost : 'N/A') . "\n";
    echo "  - New Catalog Price: " . ($bagistoProduct ? $bagistoProduct->price : 'N/A') . "\n";
    if ($history && $history->breakdown_json) {
        $freight = $history->breakdown_json['freight_adjustment'] ?? null;
        echo "  - Freight Breakdown: " . json_encode($freight, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "-------------------------------------------\n";
}
