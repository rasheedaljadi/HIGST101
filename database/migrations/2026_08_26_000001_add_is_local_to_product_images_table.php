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
        if (Schema::hasTable('product_images') && ! Schema::hasColumn('product_images', 'is_local')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->boolean('is_local')->default(false)->after('position');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'is_local')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropColumn('is_local');
            });
        }
    }
};
