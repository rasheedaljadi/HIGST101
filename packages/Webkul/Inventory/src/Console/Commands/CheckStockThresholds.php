<?php

namespace Webkul\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Notification\Services\StockNotificationService;

class CheckStockThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan all catalog products and verify low stock and out of stock thresholds to trigger admin notifications';

    /**
     * Execute the console command.
     */
    public function handle(StockNotificationService $stockNotificationService): int
    {
        $this->info('Starting inventory stock threshold scan...');

        $result = $stockNotificationService->checkAllProducts();

        $this->info("Inventory scan completed successfully. Checked {$result['checked_products']} products.");

        return self::SUCCESS;
    }
}
