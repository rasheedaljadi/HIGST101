<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$product = Webkul\Product\Models\Product::first();
if ($product) {
    $helper = app(Webkul\Product\ProductImage::class);
    $images = $helper->getGalleryImages($product);
    echo "GALLERY IMAGES RESULT:\n";
    print_r($images);
} else {
    echo "No product found.\n";
}
