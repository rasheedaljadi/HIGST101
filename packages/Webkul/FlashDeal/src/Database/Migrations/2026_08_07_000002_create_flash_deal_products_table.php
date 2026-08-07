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
        Schema::create('flash_deal_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('flash_deal_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->decimal('flash_price', 12, 4);
            $table->integer('allocation_qty');
            $table->integer('sold_qty')->default(0);
            $table->timestamps();

            $table->foreign('flash_deal_id')->references('id')->on('flash_deals')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flash_deal_products');
    }
};
