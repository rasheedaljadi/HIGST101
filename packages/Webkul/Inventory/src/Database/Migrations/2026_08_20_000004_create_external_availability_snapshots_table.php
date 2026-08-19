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
        if (! Schema::hasTable('external_availability_snapshots')) {
            Schema::create('external_availability_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->default('aliexpress');
                $table->string('external_product_id', 100)->index();
                $table->string('external_sku', 100)->index();
                $table->integer('internal_product_id')->unsigned()->nullable()->index();
                $table->integer('available_quantity')->default(0);
                $table->decimal('price_usd', 12, 4)->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->string('sync_status', 20)->default('active')->index();
                $table->timestamps();

                $table->foreign('internal_product_id')
                    ->references('id')
                    ->on('products')
                    ->onDelete('set null');

                $table->index(['provider', 'external_sku'], 'ext_avail_provider_sku_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_availability_snapshots');
    }
};
