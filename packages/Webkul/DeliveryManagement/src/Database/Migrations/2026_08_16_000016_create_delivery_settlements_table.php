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
        Schema::create('delivery_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('delivery_boy_id'); // FK to admins.id
            $table->date('settlement_date');
            $table->decimal('expected_amount', 12, 4);
            $table->decimal('actual_amount', 12, 4);
            $table->decimal('difference', 12, 4)->default(0.0000);
            $table->string('currency', 3)->default('YER');
            $table->string('status')->default('pending'); // pending, settled, discrepancy
            $table->unsignedInteger('settled_by')->nullable(); // FK to admins.id
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['delivery_boy_id', 'settlement_date']);
            $table->index(['status', 'settlement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_settlements');
    }
};
