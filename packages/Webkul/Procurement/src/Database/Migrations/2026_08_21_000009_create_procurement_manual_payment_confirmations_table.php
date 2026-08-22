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
        Schema::create('procurement_manual_payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_purchase_order_id');

            $table->unsignedInteger('confirmed_by');
            $table->timestamp('confirmed_at');
            $table->string('external_reference');
            $table->decimal('declared_total', 12, 4)->default(0.0000);
            $table->string('currency_code', 3)->default('USD');

            $table->string('evidence_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('state')->default('pending_verification');

            $table->index('external_reference', 'idx_pmpc_ext_ref');
            $table->index('state', 'idx_pmpc_state');

            $table->timestamps();

            $table->foreign('supplier_purchase_order_id', 'fk_pmpc_spo_id')
                ->references('id')
                ->on('supplier_purchase_orders')
                ->onDelete('cascade');

            $table->foreign('confirmed_by')->references('id')->on('admins')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_manual_payment_confirmations');
    }
};
