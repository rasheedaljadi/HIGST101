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
        Schema::create('financial_timeline', function (Blueprint $table) {
            $table->id();
            $table->string('event_id');
            $table->unsignedInteger('order_id');
            $table->string('event_type'); // e.g. customer_paid, supplier_charged, currency_conversion, profit_calculated
            $table->decimal('amount', 12, 4);
            $table->string('currency', 3);
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index(['order_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_timeline');
    }
};
