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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type'); // source_receipt, hayest_stock_in, reservation, package_prepared, handoff_to_delivery_party, delivery_failure_return, damage_or_loss, adjustment
            $table->unsignedInteger('product_id');
            $table->string('sku');
            $table->integer('quantity'); // Positive for inbound/restock, negative for deduction
            $table->unsignedInteger('source_inventory_source_id')->nullable();
            $table->unsignedInteger('target_inventory_source_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable();
            $table->unsignedInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('delivery_assignment_id')->nullable();
            $table->unsignedInteger('actor_id')->nullable(); // Nullable for system-initiated automated events
            $table->string('actor_type')->default('system'); // system, admin, delivery_agent
            $table->string('reference_event')->nullable(); // e.g. ProcurementCompleted, HayestStockReceived
            $table->string('job_class')->nullable(); // Class name of triggering worker job
            $table->string('idempotency_key')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'movement_type']);
            $table->index('order_id');
            $table->index('purchase_order_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
