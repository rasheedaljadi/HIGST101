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
        Schema::create('aliexpress_tokens', function (Blueprint $table) {
            $table->id();

            // AliExpress account/seller identifier returned with the token.
            $table->string('account')->nullable()->index();
            $table->string('account_id')->nullable();
            $table->string('seller_id')->nullable();

            // Tokens are stored encrypted (see model casts).
            $table->text('access_token');
            $table->text('refresh_token')->nullable();

            // Expiry tracking (epoch millis from AliExpress + computed datetime).
            $table->unsignedBigInteger('expires_in')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->unsignedBigInteger('refresh_expires_in')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();

            // Full raw token payload for auditing/debugging (encrypted).
            $table->text('payload')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aliexpress_tokens');
    }
};
