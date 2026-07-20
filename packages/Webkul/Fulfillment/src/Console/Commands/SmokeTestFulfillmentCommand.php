<?php

namespace Webkul\Fulfillment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SmokeTestFulfillmentCommand extends Command
{
    protected $signature = 'fulfillment:smoke-test';

    protected $description = 'Runs basic smoke testing checks on fulfillment components';

    public function handle()
    {
        $this->info("Starting Fulfillment Smoke Test...");

        try {
            DB::connection()->getPdo();
            $this->info("✔ Database: Connection OK");
        } catch (\Throwable $e) {
            $this->error("✘ Database: Connection failed - " . $e->getMessage());
            return 1;
        }

        try {
            Cache::put('smoke_test_key', 'ok', 60);
            if (Cache::get('smoke_test_key') === 'ok') {
                $this->info("✔ Cache / Redis: Connection OK");
            } else {
                throw new \RuntimeException("Cache read validation failed");
            }
        } catch (\Throwable $e) {
            $this->error("✘ Cache / Redis: failed - " . $e->getMessage());
            return 1;
        }

        $this->info("All basic checks PASSED.");
        return 0;
    }
}
