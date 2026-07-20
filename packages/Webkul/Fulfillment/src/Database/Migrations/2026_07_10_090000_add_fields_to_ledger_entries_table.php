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
        if (Schema::hasColumn('ledger_entries', 'purchase_order_id')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->dropColumn('purchase_order_id');
            });
        }

        if (Schema::hasColumn('ledger_entries', 'correlation_id')) {
            Schema::table('ledger_entries', function (Blueprint $table) {
                $table->dropColumn('correlation_id');
            });
        }

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('order_id');
            $table->string('correlation_id')->nullable()->after('purchase_order_id');

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn(['purchase_order_id', 'correlation_id']);
        });
    }
};
