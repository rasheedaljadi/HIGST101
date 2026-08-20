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
        Schema::create('supplier_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->string('purchase_order_number')->unique();

            $table->string('provider')->default('aliexpress');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->string('supplier_store_id')->nullable();
            $table->string('supplier_store_name')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->string('destination_signature')->default('hayest_dropship_ye');

            $table->string('state')->default('draft')->index();

            // Expected financial aggregates
            $table->decimal('expected_items_total', 12, 4)->default(0.0000);
            $table->decimal('expected_shipping_total', 12, 4)->default(0.0000);
            $table->decimal('expected_discount_total', 12, 4)->default(0.0000);
            $table->decimal('expected_total', 12, 4)->default(0.0000);

            // Actual financial aggregates
            $table->decimal('actual_items_total', 12, 4)->default(0.0000);
            $table->decimal('actual_shipping_total', 12, 4)->default(0.0000);
            $table->decimal('actual_discount_total', 12, 4)->default(0.0000);
            $table->decimal('actual_total', 12, 4)->default(0.0000);

            $table->decimal('cost_variance_amount', 12, 4)->default(0.0000);

            $table->string('payment_state')->default('unpaid')->index();
            $table->string('external_sync_state')->default('pending')->index();

            // Active uniqueness constraint key
            $table->string('active_fingerprint')->nullable()->unique('uniq_active_spo_fingerprint');
            $table->unsignedInteger('lock_version')->default(1);

            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('procurement_batches')->onDelete('cascade');

            $table->index(['batch_id', 'supplier_store_id', 'provider_account_id'], 'idx_spo_batch_store');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_orders');
    }
};
