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
        Schema::create('inventory_transfer_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('manifest_number')->unique();
            $table->string('idempotency_key')->unique();
            $table->unsignedInteger('source_inventory_source_id');
            $table->unsignedInteger('destination_inventory_source_id');
            $table->string('status')->default('draft')->index();
            $table->string('tracking_number')->nullable()->index();
            $table->string('carrier_name')->nullable();
            $table->unsignedInteger('total_packages')->default(1);
            $table->unsignedInteger('total_items_count')->default(0);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('estimated_arrival_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->unsignedInteger('received_by_admin_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('source_inventory_source_id', 'fk_itm_src_source_id')
                ->references('id')->on('inventory_sources')->onDelete('restrict');
            $table->foreign('destination_inventory_source_id', 'fk_itm_dest_source_id')
                ->references('id')->on('inventory_sources')->onDelete('restrict');
            $table->foreign('created_by_admin_id', 'fk_itm_created_admin_id')
                ->references('id')->on('admins')->onDelete('set null');
            $table->foreign('received_by_admin_id', 'fk_itm_rec_admin_id')
                ->references('id')->on('admins')->onDelete('set null');
        });

        Schema::create('inventory_transfer_manifest_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_transfer_manifest_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('order_item_id')->nullable();
            $table->unsignedBigInteger('order_allocation_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('sku')->index();
            $table->unsignedInteger('qty_shipped');
            $table->unsignedInteger('qty_received_good')->default(0);
            $table->unsignedInteger('qty_received_damaged')->default(0);
            $table->unsignedInteger('qty_received_missing')->default(0);
            $table->string('item_condition')->default('good');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inventory_transfer_manifest_id', 'fk_itmi_manifest_id')
                ->references('id')
                ->on('inventory_transfer_manifests')
                ->onDelete('cascade');
            $table->foreign('product_id', 'fk_itmi_prod_id')
                ->references('id')->on('products')->onDelete('restrict');
            $table->foreign('order_id', 'fk_itmi_order_id')
                ->references('id')->on('orders')->onDelete('set null');
            $table->foreign('order_item_id', 'fk_itmi_order_item_id')
                ->references('id')->on('order_items')->onDelete('set null');
            $table->foreign('order_allocation_id', 'fk_itmi_order_alloc_id')
                ->references('id')->on('order_allocations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_manifest_items');
        Schema::dropIfExists('inventory_transfer_manifests');
    }
};
