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
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'customer_id')) {
                $table->integer('customer_id')->unsigned()->nullable()->after('id');
                $table->foreign('customer_id', 'notifications_customer_id_foreign')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('cascade');
            }

            if (! Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (! Schema::hasColumn('notifications', 'message')) {
                $table->text('message')->nullable()->after('title');
            }

            if (! Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('message');
            }

            if (! Schema::hasColumn('notifications', 'event_key')) {
                $table->string('event_key', 191)->nullable()->after('action_url');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['customer_id', 'read'], 'notifications_customer_read_index');
            $table->index(['customer_id', 'created_at'], 'notifications_customer_created_at_index');
            $table->unique(['customer_id', 'event_key'], 'notifications_customer_event_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'customer_id')) {
                try {
                    $table->dropForeign('notifications_customer_id_foreign');
                } catch (Throwable $e) {
                }
                try {
                    $table->dropIndex('notifications_customer_read_index');
                } catch (Throwable $e) {
                }
                try {
                    $table->dropIndex('notifications_customer_created_at_index');
                } catch (Throwable $e) {
                }
                try {
                    $table->dropUnique('notifications_customer_event_unique');
                } catch (Throwable $e) {
                }

                $table->dropColumn('customer_id');
            }

            $columnsToDrop = array_filter(['title', 'message', 'action_url', 'event_key'], function ($col) {
                return Schema::hasColumn('notifications', $col);
            });

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
