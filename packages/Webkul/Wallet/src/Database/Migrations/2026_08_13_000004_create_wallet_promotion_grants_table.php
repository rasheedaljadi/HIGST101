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
        Schema::create('wallet_promotion_grants', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('promotion_id');
            $table->foreign('promotion_id')
                ->references('id')
                ->on('wallet_promotions')
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

            $table->unsignedBigInteger('usage_id');
            $table->foreign('usage_id')
                ->references('id')
                ->on('wallet_promotion_usages')
                ->onDelete('restrict');

            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->foreign('wallet_transaction_id')
                ->references('id')
                ->on('wallet_transactions')
                ->onDelete('restrict');

            $table->decimal('original_amount', 12, 4);
            $table->decimal('remaining_amount', 12, 4);
            $table->decimal('consumed_amount', 12, 4)->default(0.0000);
            $table->char('currency_code', 3);
            $table->decimal('base_amount', 12, 4);
            $table->enum('status', [
                'pending',
                'active',
                'partially_consumed',
                'fully_consumed',
                'expired',
                'reversed',
            ])->default('active');

            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->dateTime('granted_at');
            $table->dateTime('expires_at')->nullable();

            $table->timestamps();

            $table->unique('usage_id', 'unique_grant_usage');
            $table->index(['customer_id', 'status', 'expires_at', 'granted_at'], 'idx_grant_fifo');
        });

        DB::statement('ALTER TABLE `wallet_promotion_grants` ADD CONSTRAINT `chk_wpg_orig_pos` CHECK (`original_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promotion_grants` ADD CONSTRAINT `chk_wpg_rem_pos` CHECK (`remaining_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promotion_grants` ADD CONSTRAINT `chk_wpg_cons_pos` CHECK (`consumed_amount` >= 0)');
        DB::statement('ALTER TABLE `wallet_promotion_grants` ADD CONSTRAINT `chk_wpg_invariant` CHECK (`original_amount` = `remaining_amount` + `consumed_amount`)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_grants');
    }
};
