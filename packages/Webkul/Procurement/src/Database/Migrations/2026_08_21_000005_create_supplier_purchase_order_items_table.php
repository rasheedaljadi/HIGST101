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
        Schema::create('supplier_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_purchase_order_id');

            $table->string('supplier_product_id');
            $table->string('supplier_sku_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variant_product_id')->nullable();

            $table->unsignedInteger('qty_ordered')->default(0);
            $table->unsignedInteger('qty_confirmed')->default(0);
            $table->unsignedInteger('qty_received_good')->default(0);
            $table->unsignedInteger('qty_damaged')->default(0);
            $table->unsignedInteger('qty_missing')->default(0);

            $table->decimal('expected_unit_cost', 12, 4)->default(0.0000);
            $table->decimal('actual_unit_cost', 12, 4)->nullable();

            $table->json('snapshots')->nullable();

            $table->timestamps();

            $table->foreign('supplier_purchase_order_id', 'fk_spoi_order_id')
                ->references('id')
                ->on('supplier_purchase_orders')
                ->onDelete('cascade');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');

            $table->index(['supplier_purchase_order_id', 'supplier_sku_id'], 'idx_spoi_order_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_order_items');
    }
};
