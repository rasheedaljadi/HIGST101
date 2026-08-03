<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for HIGEST Pricing Engine V1.1.
     */
    public function up(): void
    {
        // 1. Source Offers — variant-centric acquisition cost snapshots.
        Schema::create('higest_source_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('variant_id');              // Strict Variant product_id
            $table->unsignedInteger('product_id');              // Parent product_id (for indexing lookup)
            $table->string('source_provider', 50)->default('aliexpress');
            $table->string('source_sku_id', 100)->nullable();
            $table->decimal('acquisition_cost', 12, 4);          // Effective acquisition cost (sale price)
            $table->decimal('acquisition_original_cost', 12, 4)->nullable(); // List price before source discount
            $table->string('source_currency', 10)->default('USD');
            $table->timestamp('captured_at');                    // When cost was captured from source
            $table->timestamp('synced_at')->nullable();          // Last sync timestamp
            $table->timestamps();

            $table->foreign('variant_id', 'hso_variant_fk')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_id', 'hso_product_fk')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['variant_id', 'source_provider'], 'hso_variant_provider_uk');
            $table->index(['product_id', 'source_provider'], 'hso_product_provider_idx');
        });

        // 2. Source Offer Histories — tracks acquisition cost shifts over time for trend auditing.
        Schema::create('higest_source_offer_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_offer_id');
            $table->unsignedInteger('variant_id');
            $table->decimal('old_acquisition_cost', 12, 4)->nullable();
            $table->decimal('new_acquisition_cost', 12, 4);
            $table->decimal('old_acquisition_original_cost', 12, 4)->nullable();
            $table->decimal('new_acquisition_original_cost', 12, 4)->nullable();
            $table->string('source_currency', 10)->default('USD');
            $table->string('change_trigger', 50);                // 'import', 'sync', 'manual'
            $table->timestamp('created_at');

            $table->foreign('source_offer_id', 'hsoh_offer_fk')->references('id')->on('higest_source_offers')->onDelete('cascade');
            $table->index(['source_offer_id', 'created_at'], 'hsoh_offer_created_idx');
        });

        // 3. Pricing Rules — merchant markup/margin rules with versioning.
        Schema::create('higest_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('scope', ['global', 'category', 'product']);
            $table->unsignedInteger('scope_id')->nullable();    // NULL for global; category_id or product_id
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 12, 4);                    // e.g. 30.0000 = 30% margin, or 10.0000 = $10 fixed
            $table->string('source_discount_policy', 50)->default('PASS_TO_CUSTOMER'); // 'PASS_TO_CUSTOMER' or 'ABSORB_BY_HIGEST'
            $table->unsignedInteger('priority')->default(0);    // Higher = more specific wins ties
            $table->unsignedInteger('version')->default(1);     // Incremental version tracking
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['scope', 'scope_id'], 'hpr_scope_idx');
            $table->index(['status', 'priority'], 'hpr_status_priority_idx');
        });

        // 4. Calculated Price Histories — domain entity tracking price calculation breakdown.
        Schema::create('higest_calculated_price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('variant_id');
            $table->unsignedInteger('product_id');
            $table->decimal('old_acquisition_cost', 12, 4)->nullable();
            $table->decimal('new_acquisition_cost', 12, 4);
            $table->decimal('old_selling_price', 12, 4)->nullable();
            $table->decimal('new_selling_price', 12, 4);
            $table->unsignedBigInteger('pricing_rule_id')->nullable();
            $table->unsignedInteger('rule_version')->nullable();
            $table->json('rule_snapshot')->nullable();           // Frozen copy of the rule at calc time
            $table->json('calculation_breakdown')->nullable();   // Pipeline stage outputs
            $table->string('trigger', 50);                       // 'import', 'sync', 'rule_change', 'manual', 'migration'
            $table->timestamp('created_at');

            $table->foreign('variant_id', 'hcph_variant_fk')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_id', 'hcph_product_fk')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('pricing_rule_id', 'hcph_rule_fk')->references('id')->on('higest_pricing_rules')->onDelete('set null');
            $table->index(['variant_id', 'created_at'], 'hcph_variant_created_idx');
            $table->index(['product_id', 'created_at'], 'hcph_product_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('higest_calculated_price_histories');
        Schema::dropIfExists('higest_pricing_rules');
        Schema::dropIfExists('higest_source_offer_histories');
        Schema::dropIfExists('higest_source_offers');
    }
};
