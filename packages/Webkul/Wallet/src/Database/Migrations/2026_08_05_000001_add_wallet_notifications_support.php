<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE notifications MODIFY order_id INT UNSIGNED NULL');

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('type');
            }
            if (! Schema::hasColumn('notifications', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE notifications MODIFY order_id INT UNSIGNED NOT NULL');

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'entity_type')) {
                $table->dropColumn('entity_type');
            }
            if (Schema::hasColumn('notifications', 'entity_id')) {
                $table->dropColumn('entity_id');
            }
        });
    }
};
