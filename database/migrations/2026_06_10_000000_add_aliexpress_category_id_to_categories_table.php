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
        Schema::table('categories', function (Blueprint $table) {
            // Links a Bagisto category to its originating AliExpress category id
            // so imported products can be attached to the matching category.
            $table->unsignedBigInteger('aliexpress_category_id')->nullable()->after('parent_id');

            $table->index('aliexpress_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['aliexpress_category_id']);
            $table->dropColumn('aliexpress_category_id');
        });
    }
};
