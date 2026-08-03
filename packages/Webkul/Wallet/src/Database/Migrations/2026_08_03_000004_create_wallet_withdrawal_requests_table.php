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
        Schema::create('wallet_withdrawal_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('wallet_id');
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallet_accounts')
                ->onDelete('restrict');

            $table->decimal('amount', 12, 4);
            $table->char('currency_code', 3);

            $table->enum('status', ['pending', 'completed', 'rejected'])->default('pending');

            // Encrypted JSON: beneficiary_name, bank_name, iban, account_number, swift_code
            $table->text('bank_details'); // Cast to 'encrypted:json' in Model

            $table->unsignedInteger('admin_user_id')->nullable();
            $table->foreign('admin_user_id')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->string('bank_transaction_reference', 255)->nullable();
            $table->timestamp('transferred_at')->nullable();

            $table->text('admin_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawal_requests');
    }
};
