<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique()->index();
            $table->integer('channel_id')->unsigned()->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_api_keys');
    }
};
