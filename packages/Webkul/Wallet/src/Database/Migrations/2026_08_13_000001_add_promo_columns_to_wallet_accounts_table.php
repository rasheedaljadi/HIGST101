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
        Schema::table('wallet_accounts', function (Blueprint $table) {
            $table->decimal('promo_balance', 12, 4)->unsigned()->default(0.0000)->after('available_balance');
            $table->decimal('cash_balance', 12, 4)->unsigned()->default(0.0000)->after('promo_balance');
            $table->decimal('unclassified_balance', 12, 4)->unsigned()->default(0.0000)->after('cash_balance');
            $table->decimal('promo_debt', 12, 4)->unsigned()->default(0.0000)->after('unclassified_balance');
            $table->enum('backfill_status', ['verified', 'pending_review', 'resolved'])->default('verified')->after('promo_debt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'promo_balance',
                'cash_balance',
                'unclassified_balance',
                'promo_debt',
                'backfill_status',
            ]);
        });
    }
};
