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
        Schema::create('procurement_demand_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('procurement_demand_id');
            $table->unsignedBigInteger('supplier_purchase_order_item_id');

            $table->unsignedInteger('qty_allocated')->default(0);
            $table->unsignedInteger('qty_ordered')->default(0);
            $table->unsignedInteger('qty_received_good')->default(0);
            $table->unsignedInteger('qty_cancelled')->default(0);

            $table->string('state')->default('allocated')->index();

            $table->timestamps();

            $table->foreign('procurement_demand_id', 'fk_pda_demand_id')
                ->references('id')
                ->on('procurement_demands')
                ->onDelete('cascade');

            $table->foreign('supplier_purchase_order_item_id', 'fk_pda_spoi_id')
                ->references('id')
                ->on('supplier_purchase_order_items')
                ->onDelete('cascade');

            $table->unique(
                ['procurement_demand_id', 'supplier_purchase_order_item_id'],
                'uniq_pda_demand_spoi'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_demand_allocations');
    }
};
