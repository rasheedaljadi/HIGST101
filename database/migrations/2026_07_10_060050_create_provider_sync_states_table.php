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
        Schema::create('provider_sync_states', function (Blueprint $table) {
            $table->string('provider')->primary();
            $table->json('last_attempt_cursor');
            $table->json('last_successful_cursor')->nullable();
            $table->timestamp('last_attempt_at');
            $table->timestamp('last_successful_at')->nullable();
            $table->timestamp('last_full_sync_at')->nullable();
            $table->integer('schema_version');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_sync_states');
    }
};
