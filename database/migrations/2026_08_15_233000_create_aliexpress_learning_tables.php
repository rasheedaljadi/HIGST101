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
        // 1. Dynamic AliExpress Category ID -> Store Category ID mappings
        Schema::create('aliexpress_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aliexpress_category_id')->index();
            $table->integer('target_category_id')->unsigned();
            $table->unsignedInteger('hits_count')->default(1);
            $table->decimal('confidence_score', 5, 2)->default(1.00);
            $table->timestamp('last_learned_at')->nullable();
            $table->timestamps();

            $table->unique(['aliexpress_category_id', 'target_category_id'], 'ae_cat_mapping_unique');
            $table->foreign('target_category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // 2. Dynamic Learned Keyword / N-Gram Weights
        Schema::create('aliexpress_keyword_weights', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100)->index();
            $table->integer('category_id')->unsigned();
            $table->unsignedInteger('frequency')->default(1);
            $table->decimal('weight', 8, 4)->default(1.0000);
            $table->timestamps();

            $table->unique(['keyword', 'category_id'], 'ae_kw_category_unique');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aliexpress_keyword_weights');
        Schema::dropIfExists('aliexpress_category_mappings');
    }
};
