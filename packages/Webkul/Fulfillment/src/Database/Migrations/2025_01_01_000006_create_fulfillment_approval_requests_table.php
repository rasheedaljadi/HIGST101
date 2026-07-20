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
        Schema::create('fulfillment_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedInteger('requested_by'); // FK to admins
            $table->string('action'); // cancel, state_override, edit
            $table->text('reason');
            $table->json('changes_payload')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, executed
            $table->unsignedInteger('approved_by')->nullable(); // FK to admins
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');
            $table->index('purchase_order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fulfillment_approval_requests');
    }
};
