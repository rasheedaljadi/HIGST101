<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');        // FK → purchase_orders.id
            $table->unsignedBigInteger('order_item_id');            // FK → order_items.id (Bagisto)
            $table->string('aliexpress_product_id')->nullable();    // من AliExpressProductImport
            $table->string('sku_id')->nullable();                   // sku_id لدى المورّد
            $table->unsignedInteger('qty');
            $table->decimal('supplier_unit_cost', 12, 4)->nullable();
            $table->timestamps();

            $table->index('purchase_order_id');
            $table->unique(['purchase_order_id', 'order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
