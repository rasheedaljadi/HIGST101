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
        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('shipment_id')->nullable();
            $table->string('delivery_type'); // home_delivery, delivery_point
            $table->unsignedInteger('delivery_boy_id')->nullable(); // FK to admins.id
            $table->unsignedBigInteger('delivery_point_id')->nullable(); // FK to delivery_points.id
            $table->string('status')->default('ready_for_assignment'); // ready_for_assignment, assigned, picked_up, out_for_delivery, arrived_at_point, delivered, delivery_failed, retry_scheduled, returned_to_hayest
            $table->unsignedInteger('assigned_by')->nullable(); // FK to admins.id
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('max_attempts')->default(2);
            $table->text('failure_reason')->nullable();
            $table->json('customer_address_snapshot')->nullable();
            $table->json('delivery_point_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['delivery_boy_id', 'status']);
            $table->index(['delivery_point_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_assignments');
    }
};
