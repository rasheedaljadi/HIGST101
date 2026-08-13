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
        Schema::create('wallet_backfill_discrepancies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict');

            $table->decimal('total_balance', 12, 4);
            $table->decimal('historical_promo_credits', 12, 4);
            $table->decimal('total_debits', 12, 4);
            $table->decimal('calculated_cash', 12, 4);
            $table->decimal('calculated_promo', 12, 4);
            $table->string('discrepancy_reason', 255);
            $table->enum('status', ['pending_review', 'resolved', 'ignored'])->default('pending_review');

            $table->unsignedInteger('resolved_by_admin_id')->nullable();
            $table->foreign('resolved_by_admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('restrict');

            $table->text('admin_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_backfill_discrepancies');
    }
};
