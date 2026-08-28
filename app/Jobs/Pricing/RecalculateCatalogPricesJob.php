<?php

namespace App\Jobs\Pricing;

use App\Enums\PricingTrigger;
use App\Services\Pricing\PriceRecalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Facades\ResponseCache;
use Throwable;

class RecalculateCatalogPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PricingTrigger|string $trigger = PricingTrigger::RULE_CHANGE,
    ) {
        $this->onQueue('pricing');
    }

    /**
     * Execute the job.
     */
    public function handle(PriceRecalculationService $recalculator): void
    {
        try {
            Log::channel('aliexpress')->info('RecalculateCatalogPricesJob: starting background recalculation', [
                'trigger' => $this->trigger instanceof PricingTrigger ? $this->trigger->value : (string) $this->trigger,
            ]);

            $count = $recalculator->recalculateAll($this->trigger);

            Artisan::call('cache:clear');
            if (class_exists(ResponseCache::class)) {
                ResponseCache::clear();
            }

            Log::channel('aliexpress')->info('RecalculateCatalogPricesJob: successfully finished recalculation', [
                'recalculated_count' => $count,
            ]);
        } catch (Throwable $e) {
            Log::channel('aliexpress')->error('RecalculateCatalogPricesJob failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
