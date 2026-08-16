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
        Schema::create('delivery_points', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. DP-SAN-01
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('state_code'); // SAN, AD, TA, etc.
            $table->string('city')->nullable();
            $table->text('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('working_hours')->nullable();
            $table->unsignedInteger('max_capacity')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['state_code', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_points');
    }
};
