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
        Schema::create('delivery_governorate_rules', function (Blueprint $table) {
            $table->id();
            $table->string('state_code'); // e.g. SAN, AD, TA
            $table->string('delivery_type'); // home_delivery, delivery_point
            $table->boolean('is_enabled')->default(true);
            $table->json('allowed_payment_methods'); // e.g. ["cashondelivery", "moneytransfer"]
            $table->decimal('delivery_fee', 10, 2)->default(0.00);
            $table->decimal('min_order_amount', 10, 2)->default(0.00);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->unique(['state_code', 'delivery_type'], 'gov_rule_state_delivery_unique');
            $table->index(['state_code', 'is_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_governorate_rules');
    }
};
