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
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('status');
            $table->string('lock_owner')->nullable();
            $table->string('worker_id')->nullable();
            $table->json('cursor');
            $table->json('metadata')->nullable();
            $table->json('health_snapshot')->nullable();
            $table->json('statistics')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
