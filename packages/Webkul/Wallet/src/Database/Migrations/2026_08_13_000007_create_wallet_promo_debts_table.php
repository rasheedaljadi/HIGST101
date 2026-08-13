<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_promo_debts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');

            $table->unsignedInteger('order_id');
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict');

            $table->unsignedInteger('source_refund_id')->nullable();
            $table->foreign('source_refund_id')
                ->references('id')
                ->on('refunds')
                ->onDelete('restrict');

            $table->string('event_key', 191);
            $table->char('currency_code', 3)->default('SAR');
            $table->decimal('original_debt_amount', 12, 4);
            $table->decimal('remaining_debt_amount', 12, 4);
            $table->decimal('settled_amount', 12, 4)->default(0.0000);
            $table->enum('status', ['active', 'partially_settled', 'settled'])->default('active');
            $table->string('reason', 255)->comment('Refund reversal deficit');

            $table->timestamps();
            $table->dateTime('settled_at')->nullable();

            $table->unique('event_key', 'unique_debt_event');
            $table->index(['customer_id', 'status'], 'idx_customer_debts');
        });

        DB::statement('ALTER TABLE `wallet_promo_debts` ADD CONSTRAINT `chk_wpd_orig_pos` CHECK (`original_debt_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promo_debts` ADD CONSTRAINT `chk_wpd_rem_pos` CHECK (`remaining_debt_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promo_debts` ADD CONSTRAINT `chk_wpd_settled_pos` CHECK (`settled_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promo_debts` ADD CONSTRAINT `chk_wpd_invariant` CHECK (`original_debt_amount` = `remaining_debt_amount` + `settled_amount`)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promo_debts');
    }
};
