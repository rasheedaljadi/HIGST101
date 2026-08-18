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
        Schema::table('inventory_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_sources', 'is_salable')) {
                $table->boolean('is_salable')->default(false)->after('status');
            }

            if (! Schema::hasColumn('inventory_sources', 'is_delivery_source')) {
                $table->boolean('is_delivery_source')->default(false)->after('is_salable');
            }

            if (! Schema::hasColumn('inventory_sources', 'source_type')) {
                $table->string('source_type')->default('general')->after('is_delivery_source');
                $table->index('source_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_sources', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_sources', 'source_type')) {
                $table->dropIndex(['source_type']);
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('inventory_sources', 'is_delivery_source')) {
                $table->dropColumn('is_delivery_source');
            }

            if (Schema::hasColumn('inventory_sources', 'is_salable')) {
                $table->dropColumn('is_salable');
            }
        });
    }
};
