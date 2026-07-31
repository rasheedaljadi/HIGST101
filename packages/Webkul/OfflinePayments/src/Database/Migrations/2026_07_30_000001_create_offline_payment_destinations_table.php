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
        Schema::create('offline_payment_destinations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('offline_payment_account_id')
                ->constrained('offline_payment_accounts')
                ->onDelete('cascade');

            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');

            $table->string('account_identifier');
            $table->string('swift_code')->nullable();
            $table->text('transfer_instructions')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Ensure unique currency per offline payment account
            $table->unique(['offline_payment_account_id', 'currency_id'], 'account_currency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_payment_destinations');
    }
};
