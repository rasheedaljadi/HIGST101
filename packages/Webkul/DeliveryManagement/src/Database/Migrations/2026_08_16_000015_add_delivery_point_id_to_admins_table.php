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
        if (Schema::hasTable('admins') && ! Schema::hasColumn('admins', 'delivery_point_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_point_id')->nullable()->after('role_id');
                $table->foreign('delivery_point_id')->references('id')->on('delivery_points')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'delivery_point_id')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropForeign(['delivery_point_id']);
                $table->dropColumn('delivery_point_id');
            });
        }
    }
};
