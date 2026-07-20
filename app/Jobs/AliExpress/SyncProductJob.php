<?php

namespace App\Jobs\AliExpress;

use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

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
        Log::channel('aliexpress')->info("SyncProductJob started processing import ID: {$this->import->id} (AliExpress ID: {$this->import->aliexpress_product_id})");
        $syncer->sync($this->import);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('aliexpress')->error("SyncProductJob failed for import ID: {$this->import->id}. Error: " . $exception->getMessage());
    }
}
