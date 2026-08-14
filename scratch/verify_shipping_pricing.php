<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$setting = \App\Models\AliExpressSetting::current();
echo "===========================================\n";
echo "AliExpress Setting include_shipping_in_price: " . ($setting->include_shipping_in_price ? 'ENABLED (true)' : 'DISABLED (false)') . "\n";
echo "===========================================\n\n";

$productIds = [1, 44, 114, 185, 222, 329, 650, 657, 658];

foreach ($productIds as $pid) {
    $bagistoProduct = \Webkul\Product\Models\Product::find($pid);
    $aeImport = \App\Models\AliExpressProductImport::where('product_id', $pid)->first();
    $sourceOffer = \App\Models\HigestSourceOffer::where('product_id', $pid)->first();
    $history = \App\Models\HigestCalculatedPriceHistory::where('product_id', $pid)->latest()->first();

    echo "Product ID: {$pid}\n";
    echo "  - AliExpress Stored Shipping Cost: " . ($aeImport ? $aeImport->base_shipping_cost . ' ' . $aeImport->shipping_currency : 'N/A') . "\n";
    echo "  - Supplier Item Cost: " . ($sourceOffer ? $sourceOffer->acquisition_cost : 'N/A') . "\n";
    echo "  - Current Selling Price in Catalog: " . ($bagistoProduct ? $bagistoProduct->price : 'N/A') . "\n";
    if ($history) {
        echo "  - History Selling Price: {$history->selling_price}\n";
        echo "  - Calculation Breakdown: " . json_encode($history->breakdown_json, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "-------------------------------------------\n";
}
