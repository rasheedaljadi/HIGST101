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
        Schema::create('wallet_promotion_grant_consumptions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('grant_id');
            $table->foreign('grant_id')
                ->references('id')
                ->on('wallet_promotion_grants')
                ->onDelete('restrict');

            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->unsignedInteger('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict');

            $table->unsignedInteger('order_item_id')->nullable();
            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->onDelete('restrict');

            $table->unsignedBigInteger('wallet_transaction_id');
            $table->foreign('wallet_transaction_id', 'fk_wpgc_txn')
                ->references('id')
                ->on('wallet_transactions')
                ->onDelete('restrict');

            $table->char('currency_code', 3)->default('SAR');
            $table->decimal('exchange_rate', 12, 4)->default(1.0000);
            $table->decimal('consumed_amount', 12, 4);
            $table->decimal('base_consumed_amount', 12, 4);
            $table->decimal('reversed_amount', 12, 4)->default(0.0000);
            $table->enum('status', ['consumed', 'partially_reversed', 'fully_reversed'])->default('consumed');

            $table->dateTime('reversed_at')->nullable();
            $table->unsignedBigInteger('reversal_transaction_id')->nullable();
            $table->foreign('reversal_transaction_id', 'fk_wpgc_rev_txn')
                ->references('id')
                ->on('wallet_transactions')
                ->onDelete('restrict');

            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id', 'idx_consumption_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_grant_consumptions');
    }
};
