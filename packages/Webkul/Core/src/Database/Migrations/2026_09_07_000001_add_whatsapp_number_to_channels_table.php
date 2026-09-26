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
        Schema::table('channels', function (Blueprint $table) {
            if (! Schema::hasColumn('channels', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable()->after('hostname');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            if (Schema::hasColumn('channels', 'whatsapp_number')) {
                $table->dropColumn('whatsapp_number');
            }
        });
    }
};
