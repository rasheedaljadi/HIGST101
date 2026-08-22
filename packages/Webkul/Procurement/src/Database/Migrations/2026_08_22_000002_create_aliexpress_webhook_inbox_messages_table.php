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
        if (! Schema::hasTable('aliexpress_webhook_inbox_messages')) {
            Schema::create('aliexpress_webhook_inbox_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('provider', 50)->default('aliexpress');
                $table->integer('event_type')->index();
                $table->string('external_event_id', 100)->nullable();
                $table->string('external_order_id', 100)->nullable()->index();
                $table->string('payload_hash', 64);
                $table->string('fingerprint', 64)->unique();
                $table->json('payload')->nullable();
                $table->dateTime('occurred_at')->nullable();
                $table->dateTime('received_at')->index();
                $table->string('status', 30)->default('received')->index();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->dateTime('processed_at')->nullable();
                $table->string('failure_code', 50)->nullable();
                $table->text('failure_message')->nullable();
                $table->timestamps();

                $table->index(['provider', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aliexpress_webhook_inbox_messages');
    }
};
