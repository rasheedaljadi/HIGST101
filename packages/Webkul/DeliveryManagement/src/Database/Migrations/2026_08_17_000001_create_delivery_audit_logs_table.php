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
        Schema::create('delivery_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_assignment_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_governorate_rule_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_point_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_settlement_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('action')->index(); // assigned, handoff, status_changed, attempt_failed, return_approved, rule_updated, settlement_processed, courier_toggled, point_toggled
            $table->string('entity_type')->nullable(); // assignment, rule, point, courier, settlement
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('delivery_assignment_id')->references('id')->on('delivery_assignments')->onDelete('cascade');
            $table->foreign('delivery_governorate_rule_id')->references('id')->on('delivery_governorate_rules')->onDelete('cascade');
            $table->foreign('delivery_point_id')->references('id')->on('delivery_points')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_audit_logs');
    }
};
