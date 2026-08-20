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
        Schema::create('procurement_cost_snapshots', function (Blueprint $table) {
            $table->id();
            $table->morphs('snapshotable', 'pcs_snap_index'); // snapshotable_type, snapshotable_id

            // expected_at_batching, expected_before_submit, actual_after_manual_payment, actual_refund
            $table->string('snapshot_type')->index();

            $table->decimal('items_subtotal', 12, 4)->default(0.0000);
            $table->decimal('shipping_amount', 12, 4)->default(0.0000);
            $table->decimal('discount_amount', 12, 4)->default(0.0000);
            $table->decimal('tax_fee_amount', 12, 4)->default(0.0000);
            $table->decimal('total_amount', 12, 4)->default(0.0000);

            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 14, 6)->default(1.000000);

            // proportionate_subtotal, quantity
            $table->string('allocation_basis')->default('proportionate_subtotal');
            $table->json('breakdown')->nullable();

            $table->string('external_reference')->nullable();
            $table->unsignedInteger('actor_id')->nullable();
            $table->string('actor_type')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->string('snapshot_hash');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['snapshotable_type', 'snapshotable_id', 'snapshot_type'], 'idx_pcs_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_cost_snapshots');
    }
};
