<?php

namespace Webkul\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Procurement\Services\ProcurementBatchService;

class ProcessProcurementAutoBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'procurement:auto-batch {--provider=aliexpress} {--currency=USD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically aggregate open procurement demands into draft/review batches';

    /**
     * Execute the console command.
     */
    public function handle(ProcurementBatchService $batchService): int
    {
        $provider = $this->option('provider');
        $currency = $this->option('currency');

        $this->info("Scanning open demands for Provider: {$provider}, Currency: {$currency}...");

        $openDemands = $batchService->getOpenDemandsQuery($provider, $currency)
            ->limit(config('procurement.batching.max_demands_per_batch', 100))
            ->pluck('id')
            ->toArray();

        if (empty($openDemands)) {
            $this->info('No open demands available for batching.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($openDemands).' demands. Creating batch...');

        try {
            $batch = $batchService->createBatch($openDemands, null);
            $this->info("Created Batch #{$batch->batch_number} with {$batch->supplierOrders->count()} supplier purchase orders.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to create batch: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
