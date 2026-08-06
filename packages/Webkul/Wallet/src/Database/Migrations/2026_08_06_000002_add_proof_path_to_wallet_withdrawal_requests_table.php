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
        Schema::table('wallet_withdrawal_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_withdrawal_requests', 'proof_path')) {
                $table->string('proof_path', 500)->nullable()->after('bank_transaction_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_withdrawal_requests', function (Blueprint $table) {
            if (Schema::hasColumn('wallet_withdrawal_requests', 'proof_path')) {
                $table->dropColumn('proof_path');
            }
        });
    }
};
