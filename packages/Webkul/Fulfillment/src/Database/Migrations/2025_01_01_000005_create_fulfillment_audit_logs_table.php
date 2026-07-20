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
        Schema::create('fulfillment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedInteger('user_id'); // FK to admins
            $table->string('action'); // retry, cancel, state_override, edit
            $table->text('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('changes_payload')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('admins')->onDelete('cascade');
            $table->index('purchase_order_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fulfillment_audit_logs');
    }
};
