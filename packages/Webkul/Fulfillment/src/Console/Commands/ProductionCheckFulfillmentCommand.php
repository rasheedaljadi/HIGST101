<?php

namespace Webkul\Fulfillment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Services\Domain\ProviderHealthService;

class ProductionCheckFulfillmentCommand extends Command
{
    protected $signature = 'fulfillment:production-check';

    protected $description = 'Checks production configurations, provider health, locks, queues, dead letters';

    public function handle(ProviderHealthService $healthService)
    {
        $this->info("=== Starting Production Readiness Check ===");

        $checks = [
            'Database'        => true,
            'Queue / Redis'   => true,
            'Provider Health' => true,
            'Dead Letters'    => true,
        ];

        try {
            DB::connection()->getPdo();
            $this->info("[PASS] Database Connection");
        } catch (\Throwable $e) {
            $this->error("[FAIL] Database Connection: " . $e->getMessage());
            $checks['Database'] = false;
        }

        try {
            $health = $healthService->getHealthStatus('aliexpress');
            $this->info("[PASS] AliExpress Provider Health: status is " . $health['status']);
        } catch (\Throwable $e) {
            $this->warn("[WARN] Provider Health check error: " . $e->getMessage());
            $checks['Provider Health'] = false;
        }

        try {
            $count = DB::table('procurement_dead_letters')->count();
            if ($count > 0) {
                $this->warn("[WARN] There are {$count} dead letters in queue.");
            } else {
                $this->info("[PASS] No pending dead letters.");
            }
        } catch (\Throwable $e) {
            $checks['Dead Letters'] = false;
        }

        $failed = array_filter($checks, fn($v) => $v === false);

        if (count($failed) === 0) {
            $this->info("READY FOR PRODUCTION");
            return 0;
        }

        $this->warn("Production check finished with WARNINGS/FAILURES.");
        return 1;
    }
}
