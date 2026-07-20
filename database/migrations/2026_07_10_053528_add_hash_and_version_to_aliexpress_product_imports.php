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
        Schema::table('aliexpress_product_imports', function (Blueprint $table) {
            $table->string('snapshot_hash', 64)->nullable()->after('payload_snapshot');
            $table->string('supplier_snapshot_version', 32)->nullable()->after('snapshot_hash');
            $table->string('external_product_version', 64)->nullable()->after('supplier_snapshot_version');
            $table->timestamp('provider_updated_at')->nullable()->after('external_product_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliexpress_product_imports', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_hash',
                'supplier_snapshot_version',
                'external_product_version',
                'provider_updated_at',
            ]);
        });
    }
};
