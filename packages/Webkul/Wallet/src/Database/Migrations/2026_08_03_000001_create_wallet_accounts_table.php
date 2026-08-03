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
        Schema::create('wallet_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('customer_id')->unique();
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('restrict'); // Customer with wallet cannot be deleted

            $table->decimal('total_balance', 12, 4)->unsigned()->default(0);
            $table->decimal('available_balance', 12, 4)->unsigned()->default(0);
            $table->decimal('held_balance', 12, 4)->unsigned()->default(0);

            $table->char('currency_code', 3)->default('SAR');

            $table->enum('status', ['active', 'suspended'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_accounts');
    }
};
