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
        Schema::table('financial_timeline', function (Blueprint $table) {
            $table->string('correlation_id')->nullable();
            $table->string('causation_id')->nullable();
            $table->string('provider')->nullable(); // maps to external_system_code
            $table->string('outbox_event_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_timeline', function (Blueprint $table) {
            $table->dropColumn(['correlation_id', 'causation_id', 'provider', 'outbox_event_id']);
        });
    }
};
