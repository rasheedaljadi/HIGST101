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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

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
            ]);

            $table->enum('direction', ['credit', 'debit']);

            $table->decimal('amount', 12, 4);

            $table->decimal('running_balance', 12, 4)->comment('available_balance after this transaction');

            $table->string('description', 500)->nullable();

            // Polymorphic reference to source (Order, Refund, WalletTopUp, WalletWithdrawalRequest)
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Self-referencing FK for ADJUSTMENT entries (Sprint 0.5: ملاحظة 3)
            $table->unsignedBigInteger('reference_transaction_id')->nullable();
            $table->foreign('reference_transaction_id')
                ->references('id')
                ->on('wallet_transactions')
                ->onDelete('set null');

            // Who created this transaction
            $table->string('created_by_type', 100)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
