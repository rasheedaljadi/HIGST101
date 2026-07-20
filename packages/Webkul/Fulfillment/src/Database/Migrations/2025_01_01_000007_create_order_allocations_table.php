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
        Schema::create('order_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_item_id');
            $table->string('allocation_type'); // supplier, warehouse
            $table->string('source_code');      // e.g., aliexpress, warehouse_riyadh
            $table->string('supplier_signature')->nullable();
            $table->unsignedInteger('reserved_qty')->default(0);
            $table->unsignedInteger('fulfilled_qty')->default(0);
            $table->unsignedInteger('canceled_qty')->default(0);
            $table->string('state')->default('reserved'); // reserved, fulfilled, canceled
            $table->unsignedInteger('version')->default(1); // Optimistic locking
            $table->timestamps();
            $table->index(['order_id', 'state']);
            $table->index('order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_allocations');
    }
};
