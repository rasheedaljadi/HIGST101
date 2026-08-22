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
        Schema::create('procurement_batch_demands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('procurement_demand_id');

            $table->unsignedInteger('qty_batched')->default(0);
            $table->unsignedInteger('qty_released')->default(0);
            $table->string('state')->default('batched')->index();

            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('procurement_batches')->onDelete('cascade');
            $table->foreign('procurement_demand_id')->references('id')->on('procurement_demands')->onDelete('cascade');

            $table->unique(['batch_id', 'procurement_demand_id'], 'uniq_batch_demand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_batch_demands');
    }
};
