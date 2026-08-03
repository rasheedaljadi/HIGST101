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
        Schema::create('wallet_pending_credits', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->unsignedInteger('refund_id');

            $table->decimal('amount', 12, 4);

            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');

            $table->unsignedInteger('attempts')->default(0);

            $table->text('error_message')->nullable();

            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'status']);
            $table->index(['refund_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_pending_credits');
    }
};
