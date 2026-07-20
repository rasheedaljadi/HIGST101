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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::dropIfExists('processed_events');
            Schema::create('processed_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider');
                $table->string('event_id');
                $table->string('event_name');
                $table->timestamp('processed_at')->useCurrent();
                $table->timestamps();
                $table->unique(['provider', 'event_id']);
            });
        } else {
            Schema::table('processed_events', function (Blueprint $table) {
                // Drop old primary key on event_id
                $table->dropPrimary('event_id');
            });

            Schema::table('processed_events', function (Blueprint $table) {
                // Add new auto-increment primary key and provider column
                $table->bigIncrements('id')->first();
                $table->string('provider')->after('id')->default('aliexpress');
                $table->unique(['provider', 'event_id'], 'processed_events_provider_event_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::dropIfExists('processed_events');
            Schema::create('processed_events', function (Blueprint $table) {
                $table->string('event_id')->primary();
                $table->string('event_name');
                $table->timestamp('processed_at')->useCurrent();
                $table->timestamps();
            });
        } else {
            Schema::table('processed_events', function (Blueprint $table) {
                $table->dropUnique('processed_events_provider_event_unique');
                $table->dropColumn(['id', 'provider']);
                $table->primary('event_id');
            });
        }
    }
};
