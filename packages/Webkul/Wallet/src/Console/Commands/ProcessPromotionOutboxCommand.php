<?php

namespace Webkul\Wallet\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Wallet\Models\WalletPromotionOutbox;
use Webkul\Wallet\Services\WalletPromotionOutboxWorker;

class ProcessPromotionOutboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:promotions:process-outbox 
                            {--batch=50 : The number of jobs to claim per batch} 
                            {--lease=60 : The lease timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending promotional jobs from the wallet promotion outbox atomically';

    /**
     * Execute the console command.
     */
    public function handle(WalletPromotionOutboxWorker $worker): int
    {
        $batch = (int) $this->option('batch');
        $lease = (int) $this->option('lease');

        $this->info("Processing promotional outbox (Batch: {$batch}, Lease: {$lease}s)...");

        $processed = $worker->runOnce($batch, $lease, 'console-worker-'.getmypid());
        $remaining = WalletPromotionOutbox::where('status', WalletPromotionOutbox::STATUS_PENDING)->count();

        $this->info("Completed: {$processed} jobs processed. Remaining pending: {$remaining}.");

        return Command::SUCCESS;
    }
}
