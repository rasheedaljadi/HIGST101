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
        Schema::create('external_platform_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_purchase_order_id');

            $table->string('provider')->default('aliexpress');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->string('supplier_store_id')->nullable();
            $table->string('external_order_id');

            $table->string('raw_status')->nullable();
            $table->string('normalized_status')->default('draft')->index();
            $table->string('currency_code', 3)->default('USD');

            $table->string('tracking_number')->nullable()->index();
            $table->string('carrier_name')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->unsignedBigInteger('payload_archive_id')->nullable();
            $table->json('snapshots')->nullable();

            $table->timestamps();

            $table->foreign('supplier_purchase_order_id', 'fk_epo_spo_id')
                ->references('id')
                ->on('supplier_purchase_orders')
                ->onDelete('cascade');

            $table->unique(['provider', 'provider_account_id', 'external_order_id'], 'uniq_provider_external_order');
        });

        Schema::create('external_platform_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_platform_order_id');
            $table->unsignedBigInteger('supplier_purchase_order_item_id');

            $table->string('external_sku_id');
            $table->unsignedInteger('quantity')->default(0);

            $table->decimal('actual_item_amount', 12, 4)->default(0.0000);
            $table->decimal('actual_shipping_amount', 12, 4)->default(0.0000);
            $table->decimal('actual_tax_amount', 12, 4)->default(0.0000);

            $table->timestamps();

            $table->foreign('external_platform_order_id', 'fk_epoi_order_id')
                ->references('id')
                ->on('external_platform_orders')
                ->onDelete('cascade');

            $table->foreign('supplier_purchase_order_item_id', 'fk_epoi_spoi_id')
                ->references('id')
                ->on('supplier_purchase_order_items')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_platform_order_items');
        Schema::dropIfExists('external_platform_orders');
    }
};
