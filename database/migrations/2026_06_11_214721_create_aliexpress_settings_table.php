<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row settings table holding the AliExpress Open Platform credentials
 * managed from the admin "Key Management" page. The app_secret is encrypted at
 * rest (see App\Models\AliExpressSetting). These values take precedence over
 * the .env / config defaults at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aliexpress_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_key')->nullable();
            $table->text('app_secret')->nullable();      // encrypted
            $table->string('redirect_uri')->nullable();   // optional manual override
            $table->string('authorize_url')->nullable();
            $table->string('token_url')->nullable();
            $table->string('business_url')->nullable();
            $table->string('sign_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aliexpress_settings');
    }
};
