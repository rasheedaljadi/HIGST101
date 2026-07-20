<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caches each imported product's AliExpress shipping data (queried once at
 * import time via aliexpress.ds.freight.query for ship-to SA / USD) so the
 * storefront carrier can compute rates locally — without calling the API while
 * a customer browses or checks out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aliexpress_product_imports', function (Blueprint $table) {
            $table->decimal('base_shipping_cost', 12, 4)->nullable()->after('images_count');
            $table->string('shipping_currency', 8)->nullable()->after('base_shipping_cost');
            $table->unsignedSmallInteger('shipping_min_days')->nullable()->after('shipping_currency');
            $table->unsignedSmallInteger('shipping_max_days')->nullable()->after('shipping_min_days');
            $table->string('shipping_company')->nullable()->after('shipping_max_days');
            $table->boolean('shipping_tracking')->nullable()->after('shipping_company');
            $table->timestamp('shipping_synced_at')->nullable()->after('shipping_tracking');
        });
    }

    public function down(): void
    {
        Schema::table('aliexpress_product_imports', function (Blueprint $table) {
            $table->dropColumn([
                'base_shipping_cost',
                'shipping_currency',
                'shipping_min_days',
                'shipping_max_days',
                'shipping_company',
                'shipping_tracking',
                'shipping_synced_at',
            ]);
        });
    }
};
