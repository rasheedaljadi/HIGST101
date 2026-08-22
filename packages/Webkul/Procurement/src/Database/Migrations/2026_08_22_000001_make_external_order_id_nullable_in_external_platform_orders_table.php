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
            $table->string('external_order_id')->nullable()->change();
            $table->string('correlation_key')->nullable()->index()->after('external_order_id');
            $table->string('provider_request_id')->nullable()->after('correlation_key');
            $table->string('failure_code')->nullable()->after('normalized_status');
            $table->text('failure_message')->nullable()->after('failure_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_platform_orders', function (Blueprint $table) {
            $table->dropColumn([
                'correlation_key',
                'provider_request_id',
                'failure_code',
                'failure_message',
            ]);
            $table->string('external_order_id')->nullable(false)->change();
        });
    }
};
