<?php

namespace Webkul\Procurement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        if (! config('procurement.v2_enabled', false)) {
            $this->warn('Procurement V2 is currently disabled (procurement.v2_enabled = false). Polling cycle aborted.');

            return self::SUCCESS;
        }

        if (! config('procurement.polling.enabled', true)) {
            $this->warn('Procurement polling is currently disabled (procurement.polling.enabled = false). Polling cycle aborted.');

            return self::SUCCESS;
        }

        $banUntil = (int) Cache::get('aliexpress:api:ban_until', 0);
        if ($banUntil > time()) {
            $remaining = $banUntil - time();
            $this->warn("AliExpress Circuit Breaker is active ({$remaining}s cooling down). Order polling skipped.");

            return self::SUCCESS;
        }

        $activeOrders = ExternalPlatformOrder::whereNotIn('normalized_status', [
            ExternalPlatformOrder::STATUS_COMPLETED,
            ExternalPlatformOrder::STATUS_CANCELLED,
        ])->get();

        if ($activeOrders->isEmpty()) {
            $this->info('No active external platform orders to poll.');

            return self::SUCCESS;
        }

        $this->info("Starting AliExpress polling cycle for {$activeOrders->count()} active order(s)...");

        foreach ($activeOrders as $order) {
            try {
                $pollingService->syncOrder($order);
                $this->line("Synced Order #{$order->external_order_id} -> {$order->normalized_status}");
            } catch (\Throwable $e) {
                $this->error("Error syncing Order #{$order->external_order_id}: {$e->getMessage()}");
            }
            usleep(500000); // 500ms throttle between orders
        }

        $this->info('Polling cycle completed successfully.');

        return self::SUCCESS;
    }
}
