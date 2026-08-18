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
        Schema::create('inbound_receipt_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('idempotency_key')->unique();
            $table->unsignedBigInteger('inventory_transfer_manifest_id')->nullable();
            $table->string('external_reference')->nullable()->index();
            $table->unsignedInteger('destination_inventory_source_id');
            $table->unsignedInteger('quarantine_inventory_source_id')->nullable();
            $table->string('status')->default('completed')->index();
            $table->unsignedInteger('received_by_admin_id')->nullable();
            $table->unsignedInteger('total_received_good')->default(0);
            $table->unsignedInteger('total_received_damaged')->default(0);
            $table->unsignedInteger('total_received_missing')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inventory_transfer_manifest_id', 'fk_irm_transfer_manifest_id')
                ->references('id')
                ->on('inventory_transfer_manifests')
                ->onDelete('set null');
            $table->foreign('destination_inventory_source_id', 'fk_irm_dest_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('restrict');
            $table->foreign('quarantine_inventory_source_id', 'fk_irm_quar_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('set null');
            $table->foreign('received_by_admin_id', 'fk_irm_admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');
        });

        Schema::create('inbound_receipt_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbound_receipt_manifest_id');
            $table->unsignedBigInteger('inventory_transfer_manifest_item_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('order_allocation_id')->nullable();
            $table->string('sku')->index();
            $table->unsignedInteger('qty_good')->default(0);
            $table->unsignedInteger('qty_damaged')->default(0);
            $table->unsignedInteger('qty_missing')->default(0);
            $table->string('condition')->default('good');
            $table->string('discrepancy_reason')->nullable();
            $table->timestamps();

            $table->foreign('inbound_receipt_manifest_id', 'fk_irmi_receipt_id')
                ->references('id')
                ->on('inbound_receipt_manifests')
                ->onDelete('cascade');
            $table->foreign('inventory_transfer_manifest_item_id', 'fk_irmi_trf_item_id')
                ->references('id')
                ->on('inventory_transfer_manifest_items')
                ->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
            $table->foreign('order_allocation_id')->references('id')->on('order_allocations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_receipt_manifest_items');
        Schema::dropIfExists('inbound_receipt_manifests');
    }
};
