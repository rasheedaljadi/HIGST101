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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('account_code'); // e.g. 1010, 2010, 4010, 5010
            $table->decimal('debit', 12, 4)->default(0.0000);
            $table->decimal('credit', 12, 4)->default(0.0000);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->index(['order_id', 'account_code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ledger_entries');
    }
};
