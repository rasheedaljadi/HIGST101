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
        });
    }
};
