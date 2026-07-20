<?php

namespace Webkul\Fulfillment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Fulfillment\Contracts\PurchaseOrder;
use Webkul\Fulfillment\Services\FulfillmentService;

class CreatePurchaseOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff;

    /**
     * Create a new job instance.
     *
     * @param  PurchaseOrder  $purchaseOrder
     */
    public function __construct(public PurchaseOrder $purchaseOrder)
    {
        $this->tries = (int) config('fulfillment.retry.max_attempts', 3);
        $this->backoff = (int) config('fulfillment.retry.backoff', 60);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        app(FulfillmentService::class)->executePurchaseOrder($this->purchaseOrder);
    }
}
