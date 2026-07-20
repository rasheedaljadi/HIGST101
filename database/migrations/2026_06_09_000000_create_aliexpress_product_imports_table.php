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
        Schema::create('aliexpress_product_imports', function (Blueprint $table) {
            $table->id();

            // Duplicate detection: one source reference per AliExpress product.
            $table->string('aliexpress_product_id')->unique();

            // Bagisto product id (soft link, no hard FK so failed/audit rows survive).
            $table->unsignedInteger('product_id')->nullable();

            // 'configurable' | 'simple'
            $table->string('type')->nullable();

            // pending|success|failed
            $table->string('status')->default('pending');

            $table->string('sku')->nullable();

            $table->unsignedSmallInteger('variants_count')->default(0);
            $table->unsignedSmallInteger('images_count')->default(0);

            // Last failure reason (must not contain secrets).
            $table->text('error')->nullable();

            // Normalized DTO snapshot for audit.
            $table->json('payload_snapshot')->nullable();

            $table->timestamps();

            $table->index('product_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aliexpress_product_imports');
    }
};
