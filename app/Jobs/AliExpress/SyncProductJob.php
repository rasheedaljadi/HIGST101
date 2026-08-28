<?php

namespace App\Jobs\AliExpress;

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 120;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected AliExpressProductImport $import
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AliExpressProductSyncer $syncer): void
    {
        $banUntil = (int) Cache::get('aliexpress:api:ban_until', 0);
        if ($banUntil > time()) {
            $remaining = $banUntil - time();
            $releaseDelay = max(60, $remaining + 10);
            Log::channel('aliexpress')->warning("SyncProductJob delayed for import ID: {$this->import->id} due to active AliExpress Circuit Breaker (waiting {$releaseDelay}s)");
            $this->release($releaseDelay);

            return;
        }

        Log::channel('aliexpress')->info("SyncProductJob started processing import ID: {$this->import->id} (AliExpress ID: {$this->import->aliexpress_product_id})");

        try {
            $syncer->sync($this->import);
        } catch (Throwable $e) {
            $err = strtolower($e->getMessage());
            if (str_contains($err, 'circuit breaker active') || str_contains($err, 'appapicalllimit') || str_contains($err, 'ban will last')) {
                $releaseDelay = 300;
                $currentBanUntil = (int) Cache::get('aliexpress:api:ban_until', 0);
                if ($currentBanUntil > time()) {
                    $releaseDelay = max(60, $currentBanUntil - time() + 10);
                }
                Log::channel('aliexpress')->warning("SyncProductJob released back to queue due to rate limit for import ID: {$this->import->id} (waiting {$releaseDelay}s)");
                $this->release($releaseDelay);

                return;
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('aliexpress')->error("SyncProductJob failed for import ID: {$this->import->id}. Error: ".$exception->getMessage());
    }
}
