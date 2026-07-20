<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('provider');
            $table->unsignedBigInteger('provider_account_id')->nullable();
            $table->string('supplier_signature')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('internal_reference')->unique();
            $table->string('external_order_id')->nullable();
            $table->string('state')->default('pending');
            $table->string('supplier_state_raw')->nullable();
            $table->json('supplier_snapshot')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_company')->nullable();
            $table->decimal('supplier_cost', 12, 4)->nullable();
            $table->string('supplier_currency', 3)->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'provider']);
            $table->index('external_order_id');
            $table->index('provider_account_id');
            
            // Composite performance indexes
            $table->index(['state', 'created_at']);
            $table->index(['provider', 'state']);
            
            // Uniqueness check for active duplicates (handled at database level via signature unique constraint)
            $table->unique(['order_id', 'provider', 'supplier_signature'], 'po_order_provider_supplier_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};
