<?php

namespace Webkul\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Services\AliExpressPollingService;

class PollAliExpressOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'procurement:poll-aliexpress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll AliExpress for order status updates, payment confirmations, and tracking numbers';

    /**
     * Execute the console command.
     */
    public function handle(AliExpressPollingService $pollingService): int
    {
        $this->info('Starting AliExpress idempotent polling cycle...');

        $activeOrders = ExternalPlatformOrder::whereNotIn('normalized_status', [
            ExternalPlatformOrder::STATUS_COMPLETED,
            ExternalPlatformOrder::STATUS_CANCELLED,
        ])->get();

        $this->info("Found {$activeOrders->count()} active external platform orders to poll.");

        foreach ($activeOrders as $order) {
            try {
                $pollingService->syncOrder($order);
                $this->line("Synced Order #{$order->external_order_id} -> {$order->normalized_status}");
            } catch (\Throwable $e) {
                $this->error("Error syncing Order #{$order->external_order_id}: {$e->getMessage()}");
            }
        }

        $this->info('Polling cycle completed successfully.');

        return self::SUCCESS;
    }
}
