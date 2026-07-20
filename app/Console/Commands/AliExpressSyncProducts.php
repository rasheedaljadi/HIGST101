<?php

namespace App\Console\Commands;

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Console\Command;
use Throwable;

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
            $deferredIds = \Illuminate\Support\Facades\Cache::pull('aliexpress-deferred-index-ids', []);
            if (empty($deferredIds)) {
                $this->info("No deferred indexes found to process.");
                return self::SUCCESS;
            }
            
            $this->info("Processing deferred indexes for " . count($deferredIds) . " products/variants...");
            
            $products = \Webkul\Product\Models\Product::whereIn('id', $deferredIds)->get();
            if ($products->isNotEmpty()) {
                $inventoryIndexer = app(\Webkul\Product\Helpers\Indexers\Inventory::class);
                $priceIndexer = app(\Webkul\Product\Helpers\Indexers\Price::class);
                $flatIndexer = app(\Webkul\Product\Helpers\Indexers\Flat::class);
                
                $inventoryIndexer->reindexBatch($products->all());
                $priceIndexer->reindexBatch($products->all());
                foreach ($products as $product) {
                    $flatIndexer->refresh($product);
                }
            }
            
            \Illuminate\Support\Facades\Log::channel('aliexpress')->info("Successfully reindexed " . count($deferredIds) . " deferred items.");
            $this->info("✓ Successfully reindexed all deferred items.");
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
            $query->where('status', 'success')->whereNotNull('product_id');
        }

        $imports = $query->get();

        if ($imports->isEmpty()) {
            $this->warn('No imported products found matching the criteria.');
            return self::SUCCESS;
        }

        $startTime = microtime(true);
        \Illuminate\Support\Facades\Log::channel('aliexpress')->info("AliExpress bulk sync session started", [
            'total_products' => $imports->count(),
            'queued' => $queue,
        ]);

        $this->info("Found {$imports->count()} product(s) to sync.");

        $success = 0;
        $failed = 0;

        foreach ($imports as $import) {
            if ($queue) {
                \App\Jobs\AliExpress\SyncProductJob::dispatch($import);
                $this->info("  ✓ Dispatched SyncProductJob for AliExpress ID: {$import->aliexpress_product_id} (Local ID: {$import->product_id})");
                $success++;
            } else {
                $this->comment("Syncing AliExpress ID: {$import->aliexpress_product_id} (Local ID: {$import->product_id})...");
                try {
                    $syncer->sync($import);
                    $this->info("  ✓ Successfully synced!");
                    $success++;
                } catch (Throwable $e) {
                    $this->error("  ✖ Failed: " . $e->getMessage());
                    $failed++;
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        \Illuminate\Support\Facades\Log::channel('aliexpress')->info("AliExpress bulk sync session completed", [
            'total_products' => $imports->count(),
            'succeeded' => $success,
            'failed' => $failed,
            'duration_seconds' => $duration,
            'queued' => $queue,
        ]);

        $this->newLine();
        if ($queue) {
            $this->info("Completed. {$success} job(s) dispatched to queue.");
        } else {
            $this->info("Completed. {$success} succeeded, {$failed} failed.");
        }

        return self::SUCCESS;
    }
}
