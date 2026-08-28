<?php

namespace App\Console\Commands;

use App\Jobs\AliExpress\SyncProductJob;
use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;
use Webkul\Fulfillment\Models\SyncRun;
use Webkul\Product\Helpers\Indexers\Flat;
use Webkul\Product\Helpers\Indexers\Inventory;
use Webkul\Product\Helpers\Indexers\Price;
use Webkul\Product\Models\Product;

class AliExpressSyncProducts extends Command
{
    protected $signature = 'aliexpress:sync-products
        {--id= : Sync a specific local product ID}
        {--all : Sync all successfully imported products}
        {--queue : Queue the sync jobs instead of running them synchronously}
        {--process-deferred-index : Process any deferred product price/inventory/flat indexes}';

    protected $description = 'Sync price and stock for imported AliExpress products';

    public function handle(AliExpressProductSyncer $syncer): int
    {
        if ($this->option('process-deferred-index')) {
            $deferredIds = Cache::pull('aliexpress-deferred-index-ids', []);
            if (empty($deferredIds)) {
                $this->info('No deferred indexes found to process.');

                return self::SUCCESS;
            }

            $this->info('Processing deferred indexes for '.count($deferredIds).' products/variants...');

            $products = Product::whereIn('id', $deferredIds)->get();
            if ($products->isNotEmpty()) {
                $inventoryIndexer = app(Inventory::class);
                $priceIndexer = app(Price::class);
                $flatIndexer = app(Flat::class);

                $inventoryIndexer->reindexBatch($products->all());
                $priceIndexer->reindexBatch($products->all());
                foreach ($products as $product) {
                    $flatIndexer->refresh($product);
                }
            }

            Log::channel('aliexpress')->info('Successfully reindexed '.count($deferredIds).' deferred items.');
            $this->info('✓ Successfully reindexed all deferred items.');

            return self::SUCCESS;
        }

        $id = $this->option('id');
        $all = $this->option('all');
        $queue = $this->option('queue');

        if (! $id && ! $all) {
            $this->error('Please specify either --id=PRODUCT_ID or --all option.');

            return self::FAILURE;
        }

        $query = AliExpressProductImport::query();

        if ($id) {
            $query->where('product_id', $id);
        } else {
            $query->where('status', 'success')
                ->whereNotNull('product_id')
                ->whereHas('product');
        }

        $imports = $query->get();

        if ($imports->isEmpty()) {
            $this->warn('No imported products found matching the criteria.');

            return self::SUCCESS;
        }

        $startTime = microtime(true);
        Log::channel('aliexpress')->info('AliExpress bulk sync session started', [
            'total_products' => $imports->count(),
            'queued' => $queue,
        ]);

        $runId = (string) Str::uuid();
        $syncRun = null;

        try {
            if (\Schema::hasTable('sync_runs')) {
                $syncRun = SyncRun::create([
                    'id' => $runId,
                    'provider' => 'aliexpress',
                    'status' => SyncRun::STATUS_CREATED,
                    'cursor' => [],
                    'metadata' => [
                        'mode' => $queue ? 'queued' : 'sync',
                        'total_products' => $imports->count(),
                    ],
                    'health_snapshot' => [
                        'memory_start' => memory_get_usage(true),
                    ],
                    'statistics' => [
                        'scanned' => $imports->count(),
                        'changed' => 0,
                        'published' => 0,
                        'errors_count' => 0,
                        'warnings_count' => 0,
                        'chunks_processed' => 1,
                    ],
                ]);
                $syncRun->start(gethostname() ?: 'cli', (string) getmypid());
            }
        } catch (Throwable $e) {
            Log::warning('Could not create SyncRun record: '.$e->getMessage());
        }

        $this->info("Found {$imports->count()} product(s) to sync.");

        $banUntil = (int) Cache::get('aliexpress:api:ban_until', 0);
        if ($banUntil > time()) {
            $remaining = $banUntil - time();
            $this->warn("AliExpress Circuit Breaker is active ({$remaining}s cooling down remaining). Sync aborted to prevent rate limiting.");

            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;
        $index = 0;

        try {
            foreach ($imports as $import) {
                if ($syncRun) {
                    try {
                        $syncRun->heartbeat();
                    } catch (Throwable $e) {
                        Log::warning('Could not update SyncRun heartbeat: '.$e->getMessage());
                    }
                }

                if ($queue) {
                    // Stagger job execution by 2 seconds per item to avoid rate limit spikes
                    $delaySeconds = $index * 2;
                    SyncProductJob::dispatch($import)->delay(now()->addSeconds($delaySeconds));
                    $this->info("  ✓ Dispatched SyncProductJob for AliExpress ID: {$import->aliexpress_product_id} (Local ID: {$import->product_id}) [Delay: +{$delaySeconds}s]");
                    $success++;
                    $index++;
                } else {
                    $this->comment("Syncing AliExpress ID: {$import->aliexpress_product_id} (Local ID: {$import->product_id})...");
                    try {
                        $syncer->sync($import);
                        $this->info('  ✓ Successfully synced!');
                        $success++;
                    } catch (Throwable $e) {
                        $this->error('  ✖ Failed: '.$e->getMessage());
                        $failed++;
                    }
                    usleep(500000); // 500ms throttle delay
                }
            }
        } catch (Throwable $fatal) {
            if ($syncRun) {
                try {
                    $syncRun->fail($fatal->getMessage());
                } catch (Throwable $e) {
                    Log::warning('Could not fail SyncRun record: '.$e->getMessage());
                }
            }
            throw $fatal;
        }

        $duration = round(microtime(true) - $startTime, 2);
        Log::channel('aliexpress')->info('AliExpress bulk sync session completed', [
            'total_products' => $imports->count(),
            'succeeded' => $success,
            'failed' => $failed,
            'duration_seconds' => $duration,
            'queued' => $queue,
        ]);

        if ($syncRun) {
            try {
                $syncRun->drain();

                $syncRun->statistics = [
                    'scanned' => $imports->count(),
                    'changed' => $success,
                    'published' => $success,
                    'errors_count' => $failed,
                    'warnings_count' => 0,
                    'chunks_processed' => 1,
                ];

                $health = [
                    'memory_peak' => memory_get_peak_usage(true),
                    'duration_sec' => $duration,
                ];

                if ($failed > 0) {
                    $syncRun->completeWithErrors($health);
                } else {
                    $syncRun->complete($health);
                }
            } catch (Throwable $e) {
                Log::warning('Could not complete SyncRun record: '.$e->getMessage());
            }
        }

        if ($success > 0) {
            try {
                Artisan::call('cache:clear');
                if (class_exists(ResponseCache::class)) {
                    ResponseCache::clear();
                }
                $this->info('✓ Catalog cache cleared.');
            } catch (Throwable $e) {
                // Ignore silent cache clear errors
            }
        }

        $this->newLine();
        if ($queue) {
            $this->info("Completed. {$success} job(s) dispatched to queue.");
        } else {
            $this->info("Completed. {$success} succeeded, {$failed} failed.");
        }

        return self::SUCCESS;
    }
}
