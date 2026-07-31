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
        Schema::create('offline_payment_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('display_name');
            $table->string('provider_name');
            $table->string('recipient_name');
            $table->string('logo_path')->nullable();

            $table->json('channel_ids');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_payment_accounts');
    }
};
