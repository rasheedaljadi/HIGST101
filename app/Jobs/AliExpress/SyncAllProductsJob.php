<?php

namespace App\Jobs\AliExpress;

use App\Models\AliExpressProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $imports = AliExpressProductImport::query()
            ->where('status', 'success')
            ->whereNotNull('product_id')
            ->get();

        Log::channel('aliexpress')->info("SyncAllProductsJob dispatched " . $imports->count() . " products for synchronization.");

        foreach ($imports as $import) {
            SyncProductJob::dispatch($import);
        }
    }
}
