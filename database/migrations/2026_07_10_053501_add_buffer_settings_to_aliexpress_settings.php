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
            $table->integer('inventory_buffer')->default(5)->after('sync_schedule');
            $table->decimal('price_change_limit', 12, 4)->default(20.00)->after('inventory_buffer');
            $table->boolean('stock_sync_enabled')->default(true)->after('price_change_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliexpress_settings', function (Blueprint $table) {
            $table->dropColumn(['inventory_buffer', 'price_change_limit', 'stock_sync_enabled']);
        });
    }
};
