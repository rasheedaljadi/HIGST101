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
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->decimal('amount', 12, 4);
            $table->char('currency_code', 3);

            $table->string('payment_method', 100)->nullable();
            $table->string('payment_transaction_id', 255)->nullable()->unique();

            // Sprint 0.5 — enhanced status machine (ملاحظة 6)
            $table->enum('status', [
                'pending_payment',
                'payment_received',
                'under_review',
                'completed',
                'failed',
                'cancelled',
                'expired',
            ])->default('pending_payment');

            $table->unsignedInteger('admin_user_id')->nullable();
            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->text('admin_notes')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
    }
};
