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
        Schema::create('external_variant_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');              // Configurable parent
            $table->unsignedInteger('variant_product_id');      // Variant simple product
            $table->string('provider')->default('aliexpress');
            $table->string('external_sku_id');
            $table->string('external_product_id');
            $table->string('external_variant_version')->nullable();
            $table->unsignedInteger('projection_version')->default(1);
            $table->timestamp('provider_updated_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_product_id')->references('id')->on('products')->onDelete('cascade');

            // Unique index for O(1) lookup of a variant product by its supplier sku ID
            $table->unique(['provider', 'external_sku_id'], 'ext_variant_provider_sku_unique');
            // Unique variant product ID index
            $table->unique('variant_product_id', 'ext_variant_prod_unique');
            // Index for parent product variant lookup
            $table->index(['product_id', 'external_sku_id'], 'ext_variant_parent_sku_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_variant_projections');
    }
};
