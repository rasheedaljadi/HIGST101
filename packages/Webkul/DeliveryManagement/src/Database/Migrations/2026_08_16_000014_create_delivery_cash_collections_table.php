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
        Schema::create('delivery_cash_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_assignment_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('delivery_boy_id'); // FK to admins.id
            $table->decimal('amount', 12, 4);
            $table->string('currency', 3)->default('YER');
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            $table->string('base_currency', 3)->default('YER');
            $table->decimal('amount_in_base_currency', 12, 4);
            $table->timestamp('rate_snapshot_at')->nullable();
            $table->timestamp('collected_at')->useCurrent();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['delivery_boy_id', 'collected_at']);
            $table->index('order_id');
            $table->index('delivery_assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_cash_collections');
    }
};
