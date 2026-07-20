<?php

namespace Webkul\Fulfillment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Webkul\Fulfillment\Models\SyncRun;

class RecoverSyncRunsCommand extends Command
{
    protected $signature = 'fulfillment:recover-sync-runs';

    protected $description = 'Recover or fail sync runs that have crashed or hung';

    public function handle(): int
    {
        $this->info("Scanning for hung or crashed sync runs...");

        // Threshold: 15 minutes of inactivity based on heartbeat_at
        $threshold = now()->subMinutes(15);

        $hungRuns = SyncRun::whereIn('status', [SyncRun::STATUS_RUNNING, SyncRun::STATUS_DRAINING, SyncRun::STATUS_RESUMING])
            ->where('heartbeat_at', '<', $threshold)
            ->get();

        if ($hungRuns->isEmpty()) {
            $this->info("No hung or crashed sync runs detected.");
            return 0;
        }

        foreach ($hungRuns as $run) {
            $this->warn("Sync run [{$run->id}] for provider [{$run->provider}] is hung (Last heartbeat: {$run->heartbeat_at}). Recovering...");

            try {
                // Transition run to INTERRUPTED state
                $run->interrupt();

                // Force release the provider distributed lock
                $lockKey = "sync:run:{$run->provider}";
                Cache::lock($lockKey)->forceRelease();

                $this->info("Sync run [{$run->id}] recovered and transitioned to INTERRUPTED state.");
            } catch (\Throwable $e) {
                $this->error("Failed to recover sync run [{$run->id}]: " . $e->getMessage());
            }
        }

        return 0;
    }
}
