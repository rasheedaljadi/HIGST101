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
        Schema::create('procurement_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();

            $table->string('provider')->default('aliexpress');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->string('destination_signature')->default('hayest_dropship_ye');

            $table->string('state')->default('draft')->index();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('source_snapshot_at')->nullable();

            $table->decimal('expected_total_cost', 12, 4)->default(0.0000);
            $table->decimal('actual_total_cost', 12, 4)->nullable();
            $table->decimal('cost_variance_amount', 12, 4)->default(0.0000);

            $table->unsignedInteger('lock_version')->default(1);

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            $table->index(['provider', 'provider_account_id', 'currency_code', 'destination_signature'], 'idx_p_batches_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_batches');
    }
};
