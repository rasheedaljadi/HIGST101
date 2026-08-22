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
        Schema::create('procurement_demands', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_item_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variant_product_id')->nullable();

            $table->string('provider')->default('aliexpress');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->string('supplier_store_id')->nullable();
            $table->string('supplier_store_name')->nullable();
            $table->string('supplier_product_id');
            $table->string('supplier_sku_id');
            $table->string('destination_source_code')->default('hayest_dropship_ye');

            $table->string('order_currency_code', 3)->default('USD');
            $table->string('supplier_currency_code', 3)->default('USD');

            // Audit & quantity counters (non-negative)
            $table->unsignedInteger('qty_requested')->default(0);
            $table->unsignedInteger('qty_covered_by_local')->default(0);
            $table->unsignedInteger('qty_required_external')->default(0);
            $table->unsignedInteger('qty_batched')->default(0);
            $table->unsignedInteger('qty_ordered_external')->default(0);
            $table->unsignedInteger('qty_received_good')->default(0);
            $table->unsignedInteger('qty_cancelled')->default(0);

            $table->string('state')->default('open_for_batching')->index();
            $table->json('source_snapshot')->nullable();
            $table->json('eligibility_snapshot')->nullable();

            // Optimistic concurrency & unique active constraint fingerprint
            $table->string('active_fingerprint')->nullable()->unique('uniq_active_demand_fingerprint');
            $table->unsignedInteger('lock_version')->default(1);

            // Cancellation metadata
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');

            // Compound indexes
            $table->index(['state', 'provider', 'supplier_currency_code', 'destination_source_code'], 'idx_p_demands_batching');
            $table->index(['supplier_store_id', 'supplier_product_id', 'supplier_sku_id'], 'idx_p_demands_store_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_demands');
    }
};
