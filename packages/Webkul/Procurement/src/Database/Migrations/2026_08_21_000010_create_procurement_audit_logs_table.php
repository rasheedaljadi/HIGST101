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
        Schema::create('procurement_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditable', 'pal_aud_index'); // auditable_type, auditable_id
            $table->string('action')->index();

            $table->unsignedInteger('actor_id')->nullable();
            $table->string('actor_type')->nullable();

            $table->string('old_state')->nullable();
            $table->string('new_state')->nullable();

            $table->json('details')->nullable();
            $table->string('correlation_id')->nullable()->index();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'action'], 'idx_pal_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_audit_logs');
    }
};
