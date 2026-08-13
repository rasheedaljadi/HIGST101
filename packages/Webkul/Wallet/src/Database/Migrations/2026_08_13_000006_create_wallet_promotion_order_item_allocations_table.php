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
        Schema::create('wallet_promotion_order_item_allocations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('usage_id');
            $table->foreign('usage_id', 'fk_wpoia_usage')
                ->references('id')
                ->on('wallet_promotion_usages')
                ->onDelete('restrict');

            $table->unsignedBigInteger('grant_id');
            $table->foreign('grant_id', 'fk_wpoia_grant')
                ->references('id')
                ->on('wallet_promotion_grants')
                ->onDelete('restrict');

            $table->unsignedInteger('order_id');
            $table->foreign('order_id', 'fk_wpoia_order')
                ->references('id')
                ->on('orders')
                ->onDelete('restrict');

            $table->unsignedInteger('invoice_id');
            $table->foreign('invoice_id', 'fk_wpoia_invoice')
                ->references('id')
                ->on('invoices')
                ->onDelete('restrict');

            $table->unsignedInteger('order_item_id');
            $table->foreign('order_item_id', 'fk_wpoia_item')
                ->references('id')
                ->on('order_items')
                ->onDelete('restrict');

            $table->string('item_sku', 100);
            $table->decimal('item_eligible_price', 12, 4);
            $table->decimal('allocated_reward', 12, 4);
            $table->decimal('base_allocated_reward', 12, 4);
            $table->decimal('reversed_reward', 12, 4)->default(0.0000);
            $table->enum('status', ['allocated', 'partially_reversed', 'fully_reversed'])->default('allocated');

            $table->timestamps();

            $table->index(['order_item_id', 'invoice_id'], 'idx_item_alloc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_order_item_allocations');
    }
};
