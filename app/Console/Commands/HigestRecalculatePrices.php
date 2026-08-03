<?php

namespace App\Console\Commands;

use App\Services\Pricing\PriceRecalculationService;
use Illuminate\Console\Command;

class HigestRecalculatePrices extends Command
{
    protected $signature = 'higest:pricing:recalculate {--trigger=manual : Trigger source (manual, migration, rule_change)}';

    protected $description = 'Recalculate selling prices for all catalog products using the HIGEST Pricing Engine';

    public function handle(PriceRecalculationService $recalculationService): int
    {
        $trigger = $this->option('trigger');

        $this->info("Starting batch price recalculation (trigger: {$trigger})...");

        $count = $recalculationService->recalculateAll($trigger);

        $this->info("Successfully recalculated selling prices for {$count} products/variants.");

        return Command::SUCCESS;
    }
}
