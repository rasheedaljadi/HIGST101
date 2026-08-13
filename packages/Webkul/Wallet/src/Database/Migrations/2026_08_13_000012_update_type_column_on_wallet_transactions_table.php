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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', [
                'CREDIT_TOPUP',
                'CREDIT_REFUND',
                'CREDIT_CANCEL',
                'RELEASE_PAYMENT',
                'DEBIT_PAYMENT',
                'HOLD_WITHDRAWAL',
                'DEBIT_WITHDRAWAL',
                'RELEASE_HOLD',
                'ADJUSTMENT',
                'SUSPENSION_FREEZE',
                'SUSPENSION_RELEASE',
            ])->change();
        });
    }
};
