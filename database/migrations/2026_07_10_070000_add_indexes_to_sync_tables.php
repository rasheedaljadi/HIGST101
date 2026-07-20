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
        Schema::table('sync_runs', function (Blueprint $table) {
            $table->index(['provider', 'status', 'heartbeat_at'], 'sync_runs_provider_status_heartbeat_idx');
        });

        Schema::table('domain_outbox_events', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'outbox_events_status_created_idx');
        });

        Schema::table('external_variant_projections', function (Blueprint $table) {
            $table->index(['provider', 'external_product_id'], 'ext_projections_provider_prod_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_runs', function (Blueprint $table) {
            $table->dropIndex('sync_runs_provider_status_heartbeat_idx');
        });

        Schema::table('domain_outbox_events', function (Blueprint $table) {
            $table->dropIndex('outbox_events_status_created_idx');
        });

        Schema::table('external_variant_projections', function (Blueprint $table) {
            $table->dropIndex('ext_projections_provider_prod_idx');
        });
    }
};
