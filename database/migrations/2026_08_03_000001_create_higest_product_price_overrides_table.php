<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for HIGEST Catalog Price Override entity.
     */
    public function up(): void
    {
        Schema::create('higest_product_price_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('variant_id');
            $table->unsignedInteger('product_id');
            $table->enum('pricing_mode', ['AUTO', 'MANUAL'])->default('AUTO');
            $table->decimal('manual_price', 12, 4)->nullable();
            $table->decimal('manual_special_price', 12, 4)->nullable();
            $table->string('override_reason', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('variant_id', 'hppo_variant_fk')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_id', 'hppo_product_fk')->references('id')->on('products')->onDelete('cascade');
            $table->unique('variant_id', 'hppo_variant_uk');
            $table->index(['product_id', 'pricing_mode'], 'hppo_product_mode_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('higest_product_price_overrides');
    }
};
