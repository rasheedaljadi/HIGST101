<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAllProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'higest:clear-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely wipe all catalog products, images, attributes, and import logs to allow fresh product imports.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting catalog cleanup...');

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

        $this->info('All catalog products and import records cleared successfully!');

        return self::SUCCESS;
    }
}
