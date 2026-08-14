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
        if (Schema::hasTable('aliexpress_settings')) {
            Schema::table('aliexpress_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('aliexpress_settings', 'exclude_choice_from_shipping_price')) {
                    $table->boolean('exclude_choice_from_shipping_price')->default(true)->after('include_shipping_in_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('aliexpress_settings')) {
            Schema::table('aliexpress_settings', function (Blueprint $table) {
                if (Schema::hasColumn('aliexpress_settings', 'exclude_choice_from_shipping_price')) {
                    $table->dropColumn('exclude_choice_from_shipping_price');
                }
            });
        }
    }
};
