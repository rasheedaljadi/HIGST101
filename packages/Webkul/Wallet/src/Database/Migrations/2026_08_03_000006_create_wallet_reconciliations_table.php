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
        Schema::create('wallet_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->timestamp('run_at');

            $table->unsignedInteger('total_wallets_audited')->default(0);

            $table->unsignedInteger('discrepancies_count')->default(0);

            $table->decimal('total_system_liability', 12, 4)->default(0);

            $table->enum('status', ['clean', 'discrepancy_detected'])->default('clean');

            $table->json('report_summary')->nullable();

            $table->timestamps();

            $table->index(['status', 'run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_reconciliations');
    }
};
