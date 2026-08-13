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
        Schema::create('wallet_promo_debt_settlements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('debt_id');
            $table->foreign('debt_id', 'fk_wpds_debt')
                ->references('id')
                ->on('wallet_promo_debts')
                ->onDelete('restrict');

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id', 'fk_wpds_wallet')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id', 'fk_wpds_cust')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');

            $table->unsignedBigInteger('grant_id');
            $table->foreign('grant_id', 'fk_wpds_grant')
                ->references('id')
                ->on('wallet_promotion_grants')
                ->onDelete('restrict');

            $table->decimal('settlement_amount', 12, 4);
            $table->decimal('base_settlement_amount', 12, 4);
            $table->char('currency_code', 3)->default('SAR');

            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->foreign('wallet_transaction_id', 'fk_wpds_txn')
                ->references('id')
                ->on('wallet_transactions')
                ->onDelete('restrict');

            $table->string('event_key', 191);
            $table->timestamp('created_at')->useCurrent();

            $table->unique('event_key', 'unique_debt_settlement');
            $table->index(['customer_id', 'debt_id'], 'idx_settlement_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promo_debt_settlements');
    }
};
