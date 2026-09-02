<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aliexpress_settings', function (Blueprint $table) {
            $table->string('variance_product_type', 20)->default('percentage')->after('stock_sync_enabled');
            $table->decimal('variance_product_limit', 12, 4)->default(10.0000)->after('variance_product_type');
            $table->string('variance_shipping_type', 20)->default('percentage')->after('variance_product_limit');
            $table->decimal('variance_shipping_limit', 12, 4)->default(15.0000)->after('variance_shipping_type');
            $table->boolean('variance_auto_approve')->default(true)->after('variance_shipping_limit');
            $table->boolean('variance_profit_guard_enabled')->default(true)->after('variance_auto_approve');
            $table->decimal('variance_min_profit_margin', 12, 4)->default(5.0000)->after('variance_profit_guard_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliexpress_settings', function (Blueprint $table) {
            $table->dropColumn([
                'variance_product_type',
                'variance_product_limit',
                'variance_shipping_type',
                'variance_shipping_limit',
                'variance_auto_approve',
                'variance_profit_guard_enabled',
                'variance_min_profit_margin',
            ]);
        });
    }
};
