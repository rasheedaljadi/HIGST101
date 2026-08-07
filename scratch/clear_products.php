<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Clearing all products and import records...\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

if (Schema::hasTable('ali_express_product_imports')) {
    DB::table('ali_express_product_imports')->truncate();
}

if (Schema::hasTable('external_variant_projections')) {
    DB::table('external_variant_projections')->truncate();
}

if (Schema::hasTable('higest_source_offers')) {
    DB::table('higest_source_offers')->truncate();
}

if (Schema::hasTable('flash_deal_products')) {
    DB::table('flash_deal_products')->truncate();
}

if (Schema::hasTable('wishlist')) {
    DB::table('wishlist')->truncate();
}

if (Schema::hasTable('cart_items')) {
    DB::table('cart_items')->truncate();
}

DB::table('products')->truncate();
DB::table('product_flat')->truncate();
DB::table('product_images')->truncate();
DB::table('product_videos')->truncate();
DB::table('product_attribute_values')->truncate();
DB::table('product_inventories')->truncate();
DB::table('product_categories')->truncate();
DB::table('product_super_attributes')->truncate();
DB::table('product_relations')->truncate();
DB::table('product_cross_sells')->truncate();
DB::table('product_up_sells')->truncate();
DB::table('product_grouped_products')->truncate();

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "All products cleared successfully!\n";
