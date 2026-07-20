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
        Schema::create('external_inbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // maps to external_system_code
            $table->string('event_id');
            $table->string('event_type');
            $table->string('aggregate_type')->nullable();
            $table->string('aggregate_id')->nullable();
            $table->json('payload');
            $table->text('signature')->nullable();
            $table->string('status')->default('pending'); // pending, processing, processed, failed, dead_letter
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->string('processing_lock_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_inbox_events');
    }
};
