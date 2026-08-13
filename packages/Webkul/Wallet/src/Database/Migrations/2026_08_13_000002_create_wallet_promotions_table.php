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
        Schema::create('wallet_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', [
                'welcome_bonus',
                'topup_bonus',
                'order_subtotal_cashback',
                'order_conditional_cashback',
            ]);
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->enum('action_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('reward_value', 12, 4);
            $table->decimal('max_reward_amount', 12, 4)->nullable();
            $table->decimal('min_spend_amount', 12, 4)->nullable();
            $table->unsignedInteger('grant_validity_days')->nullable()->comment('Days before granted bonus expires');
            $table->decimal('total_budget', 12, 4)->nullable();
            $table->decimal('total_allocated', 12, 4)->default(0.0000);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_per_customer')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->dateTime('starts_from')->nullable();
            $table->dateTime('ends_till')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('end_other_promotions')->default(false);

            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->foreign('created_by_admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('restrict');

            $table->timestamps();

            $table->index(['type', 'status', 'starts_from', 'ends_till'], 'idx_promo_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotions');
    }
};
