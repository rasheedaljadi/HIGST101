<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the admin-managed shipping settings:
 *  - shipping_margin: a flat amount (store currency, USD) added on top of the
 *    AliExpress base shipping cost. Covers the internal SA -> Yemen -> customer
 *    leg and the store's margin.
 *  - shipping_extra_days: extra delivery days appended to the AliExpress
 *    estimate to reflect the internal transfer leg.
 *  - shipping_enabled: toggle for the AliExpress storefront carrier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aliexpress_settings', function (Blueprint $table) {
            $table->decimal('shipping_margin', 12, 4)->default(0)->after('sign_method');
            $table->unsignedSmallInteger('shipping_extra_days')->default(0)->after('shipping_margin');
            $table->boolean('shipping_enabled')->default(true)->after('shipping_extra_days');
        });
    }

    public function down(): void
    {
        Schema::table('aliexpress_settings', function (Blueprint $table) {
            $table->dropColumn(['shipping_margin', 'shipping_extra_days', 'shipping_enabled']);
        });
    }
};
