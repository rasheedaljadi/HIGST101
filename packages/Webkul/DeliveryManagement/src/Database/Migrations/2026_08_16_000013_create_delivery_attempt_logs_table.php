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
        Schema::create('delivery_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_assignment_id');
            $table->unsignedInteger('attempt_number');
            $table->string('status'); // success, failed, rescheduled
            $table->text('failure_reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->unsignedInteger('attempted_by'); // FK to admins.id
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('delivery_assignment_id');
            $table->index('attempted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_attempt_logs');
    }
};
