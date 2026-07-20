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
        Schema::create('external_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // maps to external_system_code
            $table->timestamp('last_success')->nullable();
            $table->timestamp('last_failure')->nullable();
            $table->float('failure_rate')->default(0.0);
            $table->string('status')->default('healthy'); // healthy, slow, down
            $table->integer('latency_ms')->default(0);
            $table->integer('last_http_status')->nullable();
            $table->string('last_error_code')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_health_checks');
    }
};
