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
        Schema::create('wallet_promotion_outbox', function (Blueprint $table) {
            $table->id();

            $table->string('event_type', 100);
            $table->string('event_key', 191);
            $table->json('payload');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->dateTime('locked_at')->nullable();
            $table->string('locked_by', 100)->nullable();
            $table->dateTime('lease_expires_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('processed_at')->nullable();

            $table->unique('event_key', 'unique_outbox_event');
            $table->index(['status', 'lease_expires_at', 'attempts'], 'idx_outbox_claim');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_outbox');
    }
};
