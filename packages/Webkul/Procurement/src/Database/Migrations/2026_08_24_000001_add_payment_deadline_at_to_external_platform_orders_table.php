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
        Schema::table('external_platform_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('external_platform_orders', 'payment_deadline_at')) {
                $table->timestamp('payment_deadline_at')->nullable()->after('last_synced_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_platform_orders', function (Blueprint $table) {
            if (Schema::hasColumn('external_platform_orders', 'payment_deadline_at')) {
                $table->dropColumn('payment_deadline_at');
            }
        });
    }
};
