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
        Schema::table('order_allocations', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->after('order_item_id');
            $table->unsignedInteger('variant_product_id')->nullable()->after('product_id');
            $table->json('supplier_snapshot')->nullable()->after('state');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('variant_product_id')->references('id')->on('products')->onDelete('set null');

            // Indexes
            $table->index(['variant_product_id', 'state'], 'alloc_variant_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_allocations', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variant_product_id']);
            $table->dropIndex('alloc_variant_state_idx');
            
            $table->dropColumn(['product_id', 'variant_product_id', 'supplier_snapshot']);
        });
    }
};
