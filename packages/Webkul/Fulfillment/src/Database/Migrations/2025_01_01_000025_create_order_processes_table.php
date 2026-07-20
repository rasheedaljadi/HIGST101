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
        Schema::create('order_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->unique();
            $table->string('payment_mode'); // prepaid, cod
            $table->string('lifecycle_state')->default('waiting_payment'); // waiting_payment, pending_acceptance, accepted, fulfillment_started, ops_review, ready_to_ship, settled
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_by')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->string('correlation_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_processes');
    }
};
