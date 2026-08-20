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
        Schema::create('order_lifecycle_stage_views', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->unsigned()->unique();
            $table->string('current_stage_code', 50)->index();
            $table->string('bottleneck_stage_code', 50)->index();
            $table->boolean('is_mixed_order')->default(false);
            $table->boolean('has_imported_items')->default(false);
            $table->boolean('has_internal_items')->default(false);
            $table->boolean('is_exception')->default(false);
            $table->string('exception_reason', 100)->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->string('source_version', 50)->default('v1.0');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index('computed_at');
        });

        Schema::create('order_item_lifecycle_stage_views', function (Blueprint $table) {
            $table->id();
            $table->integer('order_item_id')->unsigned()->unique();
            $table->integer('order_id')->unsigned()->index();
            $table->string('origin_type', 50)->index(); // 'internal' or 'imported'
            $table->string('current_stage_code', 50)->index();
            $table->string('source_type', 50)->nullable(); // e.g. 'hayest_internal_ye', 'hayest_dropship_ye'
            $table->string('source_entity_type', 100)->nullable();
            $table->unsignedBigInteger('source_entity_id')->nullable();
            $table->boolean('is_exception')->default(false);
            $table->string('exception_reason', 100)->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_lifecycle_stage_views');
        Schema::dropIfExists('order_lifecycle_stage_views');
    }
};
