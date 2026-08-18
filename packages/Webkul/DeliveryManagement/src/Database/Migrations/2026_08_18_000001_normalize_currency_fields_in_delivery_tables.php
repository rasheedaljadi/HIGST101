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
        // 1. Normalize delivery_cash_collections
        Schema::table('delivery_cash_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_cash_collections', 'order_currency_code')) {
                $table->string('order_currency_code', 3)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('delivery_cash_collections', 'order_amount')) {
                $table->decimal('order_amount', 12, 4)->nullable()->after('order_currency_code');
            }
            if (! Schema::hasColumn('delivery_cash_collections', 'collected_currency_code')) {
                $table->string('collected_currency_code', 3)->nullable()->after('order_amount');
            }
            if (! Schema::hasColumn('delivery_cash_collections', 'collected_amount')) {
                $table->decimal('collected_amount', 12, 4)->nullable()->after('collected_currency_code');
            }

            // Remove legacy hardcoded 'YER' defaults
            $table->string('currency', 3)->nullable()->default(null)->change();
            $table->string('base_currency', 3)->nullable()->default(null)->change();
        });

        // 2. Normalize delivery_settlements
        Schema::table('delivery_settlements', function (Blueprint $table) {
            // Remove legacy hardcoded 'YER' default
            $table->string('currency', 3)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_cash_collections', function (Blueprint $table) {
            $table->dropColumn([
                'order_currency_code',
                'order_amount',
                'collected_currency_code',
                'collected_amount',
            ]);

            $table->string('currency', 3)->nullable()->default(null)->change();
            $table->string('base_currency', 3)->nullable()->default(null)->change();
        });

        Schema::table('delivery_settlements', function (Blueprint $table) {
            $table->string('currency', 3)->nullable()->default(null)->change();
        });
    }
};
