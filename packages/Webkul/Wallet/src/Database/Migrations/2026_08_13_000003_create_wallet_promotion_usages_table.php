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
        Schema::create('wallet_promotion_usages', function (Blueprint $table) {
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

            $table->string('event_key', 191);
            $table->decimal('reward_amount', 12, 4);
            $table->decimal('base_reward_amount', 12, 4);
            $table->decimal('net_credited_amount', 12, 4)->default(0.0000)->comment('Actual net amount credited to wallet after debt settlement');
            $table->char('currency_code', 3);
            $table->decimal('exchange_rate', 12, 4)->default(1.0000);
            $table->enum('status', ['pending', 'approved', 'reversed', 'rejected'])->default('pending');
            $table->json('promotion_snapshot')->comment('Immutable snapshot of promotion rules at grant time');
            $table->json('decision_meta')->nullable()->comment('Reasoning and conflict resolution logs');

            $table->timestamps();

            $table->unique(['promotion_id', 'event_key'], 'unique_usage_event');
            $table->index(['customer_id', 'status'], 'idx_customer_usages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_usages');
    }
};
