<?php

namespace Webkul\Fulfillment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Contracts\SyncProviderInterface;
use Webkul\Fulfillment\Exceptions\RateLimitExceededException;
use Webkul\Fulfillment\Services\Application\SyncEngine;

class SyncProductBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $providerName,
        public readonly int $batchSize = 50
    ) {}

    public function handle(SyncEngine $engine): void
    {
        $providers = config('sync.providers', []);
        $providerClass = $providers[$this->providerName] ?? null;

        if (!$providerClass) {
            Log::error("Fulfillment sync provider [{$this->providerName}] is not configured.");
            return;
        }

        if (!class_exists($providerClass)) {
            Log::error("Fulfillment sync provider class [{$providerClass}] does not exist.");
            return;
        }

        $provider = app($providerClass);

        if (!$provider instanceof SyncProviderInterface) {
            Log::error("Fulfillment sync provider class [{$providerClass}] must implement SyncProviderInterface.");
            return;
        }

        try {
            $engine->execute($this->providerName, $provider, $this->batchSize);
        } catch (RateLimitExceededException $e) {
            // Requeue the job with delay to prevent blocking workers
            Log::warning("Fulfillment sync job rate limited. Releasing job back to queue. Details: " . $e->getMessage());
            $this->release($e->getRetryAfter());
        } catch (\Throwable $e) {
            Log::error("Fulfillment sync job failed: " . $e->getMessage());
            throw $e;
        }
    }
}
