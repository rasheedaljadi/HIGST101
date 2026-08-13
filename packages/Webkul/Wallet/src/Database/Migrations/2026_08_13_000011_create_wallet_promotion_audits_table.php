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
        Schema::create('wallet_promotion_audits', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('promotion_id');
            $table->foreign('promotion_id')
                ->references('id')
                ->on('wallet_promotions')
                ->onDelete('restrict');

            $table->unsignedInteger('admin_user_id');
            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admins')
                ->onDelete('restrict');

            $table->enum('action', [
                'created',
                'updated',
                'activated',
                'deactivated',
                'archived',
                'manual_adjustment',
            ]);

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_promotion_audits');
    }
};
