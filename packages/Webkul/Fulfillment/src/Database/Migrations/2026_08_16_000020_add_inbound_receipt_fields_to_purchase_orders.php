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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('receipt_status')->default('not_received')->after('state'); // not_received, inbound_receipt_pending, full_receipt_confirmed, discrepancy_reported
            $table->timestamp('receipt_confirmed_at')->nullable()->after('submitted_at');
            $table->unsignedInteger('receipt_confirmed_by')->nullable()->after('receipt_confirmed_at');
            $table->text('receipt_notes')->nullable()->after('receipt_confirmed_by');
            $table->json('receipt_discrepancy_data')->nullable()->after('receipt_notes');

            $table->index('receipt_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex(['receipt_status']);
            $table->dropColumn([
                'receipt_status',
                'receipt_confirmed_at',
                'receipt_confirmed_by',
                'receipt_notes',
                'receipt_discrepancy_data',
            ]);
        });
    }
};
