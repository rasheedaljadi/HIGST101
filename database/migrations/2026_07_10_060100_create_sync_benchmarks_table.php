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
        Schema::create('sync_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date');
            $table->string('provider');
            $table->double('throughput');
            $table->bigInteger('memory_peak_bytes');
            $table->integer('latency_avg_ms');
            $table->integer('products_changed');
            $table->integer('products_unchanged');
            $table->integer('stale_events');
            $table->integer('replay_events');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_benchmarks');
    }
};
