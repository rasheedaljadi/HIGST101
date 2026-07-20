<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. domain_outbox_events
        Schema::create('domain_outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_name');
            $table->integer('event_version')->default(1);
            $table->string('aggregate_type')->nullable();
            $table->string('aggregate_id')->nullable();
            $table->string('correlation_id')->nullable();
            $table->string('causation_id')->nullable();
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamps();

            // Indexes for fast querying by dispatcher and replay analytics
            $table->index(['event_name', 'status'], 'outbox_event_name_status_idx');
            $table->index(['aggregate_type', 'aggregate_id'], 'outbox_aggregate_idx');
            $table->index(['status', 'created_at'], 'outbox_status_created_idx');
        });

        // 2. domain_outbox_event_attempts
        Schema::create('domain_outbox_event_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outbox_event_id');
            $table->string('listener');
            $table->integer('attempt_number');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('outbox_event_id', 'outbox_attempts_event_id_fk')
                ->references('id')
                ->on('domain_outbox_events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('domain_outbox_event_attempts');
        Schema::dropIfExists('domain_outbox_events');
    }
};
