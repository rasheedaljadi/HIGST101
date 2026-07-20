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
        Schema::create('external_systems', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // e.g. supplier, carrier, gateway
            $table->boolean('enabled')->default(true);
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        // Seed default aliexpress external system
        DB::table('external_systems')->insert([
            'code'       => 'aliexpress',
            'name'       => 'AliExpress Dropshipping System',
            'type'       => 'supplier',
            'enabled'    => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_systems');
    }
};
