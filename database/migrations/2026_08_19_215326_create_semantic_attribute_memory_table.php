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
        Schema::create('semantic_attribute_memory', function (Blueprint $table) {
            $table->id();

            // Unique key: (value signature + original axis code + category context)
            $table->string('value_signature', 64)->comment('SHA-256 of sorted, cleaned values');
            $table->string('original_axis_code', 100)->comment('Original axis code from provider (e.g. ae_size)');
            $table->string('category_context', 100)->nullable()->default(null)->comment('Store category slug or "global"');

            // Classification result
            $table->string('semantic_type', 50)->comment('e.g. storage_capacity, color, shoe_size');
            $table->string('arabic_label', 200)->comment('Proper Arabic label (e.g. سعة التخزين)');
            $table->string('english_label', 200)->comment('Proper English label (e.g. Storage Capacity)');
            $table->string('classified_by', 30)->comment('pattern | visual | contextual | admin_override');
            $table->decimal('confidence', 3, 2)->comment('0.00 — 1.00');

            // Learning stats
            $table->unsignedInteger('hits_count')->default(1);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(
                ['value_signature', 'original_axis_code', 'category_context'],
                'uq_signature_axis_category'
            );
            $table->index('semantic_type', 'idx_semantic_type');
            $table->index('hits_count', 'idx_hits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semantic_attribute_memory');
    }
};
