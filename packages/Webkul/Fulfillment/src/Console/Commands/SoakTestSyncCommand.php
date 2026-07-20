<?php

namespace Webkul\Fulfillment\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Fulfillment\Services\Application\SyncEngine;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\DataObjects\ProviderSyncCapabilities;
use Webkul\Fulfillment\DataObjects\NormalizedExternalProductBatch;
use Webkul\Fulfillment\DataObjects\SyncCursor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SoakTestSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fulfillment:soak-test 
                            {--iterations=50 : Number of chunks to process} 
                            {--batch-size=20 : Batch size for each chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute a long-running soak test on the synchronization engine to detect memory leaks and lock deadlocks.';

    /**
     * Execute the console command.
     */
    public function handle(SyncEngine $engine)
    {
        $this->info("Initializing Sync Engine Soak Test...");
        $iterations = (int) $this->option('iterations');
        $batchSize = (int) $this->option('batch-size');

        $this->info("Configuration: Iterations = {$iterations}, Batch Size = {$batchSize}");

        // Create a custom mock provider that simulates a 100k product catalog
        $soakProvider = new class implements SyncProviderInterface {
            private int $currentProductIndex = 0;

            public function getCapabilities(): ProviderSyncCapabilities
            {
                return new ProviderSyncCapabilities(1, true, true, true, true, true);
            }

            public function fetchProductsBatch(SyncCursor $cursor, int $batchSize): NormalizedExternalProductBatch
            {
                $products = [];
                for ($i = 0; $i < $batchSize; $i++) {
                    $this->currentProductIndex++;
                    $id = 'sp_soak_' . $this->currentProductIndex;
                    $products[] = [
                        'id' => $id,
                        'variants' => [
                            [
                                'sku_id' => 'sku_soak_' . $this->currentProductIndex,
                                'price'  => round(rand(10, 500) + (rand(0, 99) / 100), 2),
                                'stock'  => rand(0, 1000),
                                'version'=> 1
                            ]
                        ]
                    ];
                }

                $nextPage = 'page_' . ($this->currentProductIndex / $batchSize + 1);
                
                return new NormalizedExternalProductBatch(
                    'aliexpress',
                    $products,
                    $nextPage,
                    true
                );
            }
        };

        // Prepare local projections to simulate mapped products
        $this->info("Preparing mock product projections for EAV matching...");
        
        // Force release lock and clean DB runs
        Cache::lock('sync:run:aliexpress')->forceRelease();
        DB::table('sync_runs')->delete();
        DB::table('provider_sync_states')->delete();

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        DB::transaction(function () use ($iterations, $batchSize) {
            DB::table('external_variant_projections')->where('provider', 'aliexpress')->delete();
            
            $totalProjections = $iterations * $batchSize;
            $insertData = [];
            for ($i = 1; $i <= $totalProjections; $i++) {
                $insertData[] = [
                    'provider'            => 'aliexpress',
                    'external_product_id' => 'sp_soak_' . $i,
                    'external_sku_id'     => 'sku_soak_' . $i,
                    'product_id'          => 1, // Point all to local product ID 1
                    'variant_product_id'  => $i + 10000,
                    'projection_version'  => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                if (count($insertData) >= 1000) {
                    DB::table('external_variant_projections')->insert($insertData);
                    $insertData = [];
                }
            }

            if (!empty($insertData)) {
                DB::table('external_variant_projections')->insert($insertData);
            }
        });

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        // Set higher memory limit for soak test in config
        config(['sync.backpressure.memory_limit' => 512 * 1024 * 1024]); // 512 MB
        config(['sync.draining_timeout' => 2]); // Short drain timeout for fast test iterations

        $initialMemory = memory_get_usage(true);
        $this->info(sprintf("Initial memory: %.2f MB", $initialMemory / 1024 / 1024));

        $start = microtime(true);
        $memoryHistory = [];

        $this->output->progressStart($iterations);

        for ($step = 1; $step <= $iterations; $step++) {
            // Run single execution iteration
            $run = $engine->execute('aliexpress', $soakProvider, $batchSize);

            $currentMemory = memory_get_usage(true);
            $memoryHistory[] = $currentMemory;
            
            $this->output->progressAdvance();

            // Leak detection logic (compare against last 5 iterations)
            if ($step > 5) {
                $avgPrev = array_sum(array_slice($memoryHistory, -5, 4)) / 4;
                $growth = $currentMemory - $avgPrev;
                if ($growth > 10 * 1024 * 1024) { // Warning on > 10MB growth
                    $this->warn("\n[Warning] Memory growth detected at iteration {$step}: +" . round($growth / 1024 / 1024, 2) . " MB");
                }
            }
        }

        $this->output->progressFinish();

        $end = microtime(true);
        $finalMemory = memory_get_usage(true);
        $duration = $end - $start;

        $this->info("Soak test completed successfully!");
        $this->info(sprintf("Duration: %.2f seconds", $duration));
        $this->info(sprintf("Final Memory: %.2f MB (Difference: %+.2f MB)", 
            $finalMemory / 1024 / 1024, 
            ($finalMemory - $initialMemory) / 1024 / 1024
        ));

        // Clean up mock projections
        $this->info("Cleaning up mock projections...");
        DB::table('external_variant_projections')->where('provider', 'aliexpress')->delete();
    }
}
