<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ClearSyncLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'higest:clear-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely clear all synchronization records, event logs, outbox/inbox queues, external projections, and sync log files.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting synchronization data cleanup...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'ali_express_product_imports',
            'external_variant_projections',
            'external_order_projections',
            'external_payload_archives',
            'external_api_logs',
            'external_inbox_events',
            'external_health_checks',
            'domain_outbox_events',
            'domain_outbox_event_attempts',
            'processed_events',
            'procurement_sagas',
            'procurement_aggregates',
            'procurement_sessions',
            'procurement_commands',
            'procurement_inbox_events',
            'procurement_dead_letters',
            'procurement_dashboard_projections',
            'procurement_timelines',
            'procurement_metrics',
            'outgoing_requests',
            'fulfillment_audit_logs',
            'fulfillment_provider_events',
            'fulfillment_attempts',
            'allocation_logs',
            'higest_source_offers',
            'jobs',
            'failed_jobs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("Cleared table: {$table}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Truncate sync log files
        $logPaths = [
            storage_path('logs/aliexpress.log'),
            storage_path('logs/fulfillment.log'),
            storage_path('logs/laravel.log'),
        ];

        foreach ($logPaths as $path) {
            if (File::exists($path)) {
                File::put($path, '');
                $this->line('Cleared log file: '.basename($path));
            }
        }

        $this->info('All synchronization records, logs, and queue events cleared successfully!');

        return self::SUCCESS;
    }
}
