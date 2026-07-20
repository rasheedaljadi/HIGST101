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
        Schema::create('allocation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_allocation_id');
            $table->string('action'); // reserve, fulfill, cancel, edit
            $table->unsignedInteger('old_qty');
            $table->unsignedInteger('new_qty');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('order_allocation_id')->references('id')->on('order_allocations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('allocation_logs');
    }
};
