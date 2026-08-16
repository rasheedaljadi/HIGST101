<?php

namespace Webkul\Fulfillment\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Events\Procurement\ProcurementCompleted;
use Webkul\Fulfillment\Services\InboundReceiptService;

class ProcurementCompletedListener
{
    public function __construct(
        protected InboundReceiptService $inboundReceiptService
    ) {}

    /**
     * Handle the ProcurementCompleted event.
     * Marks the purchase order as pending physical inbound receipt without modifying inventory.
     */
    public function handle(ProcurementCompleted $event): void
    {
        try {
            $this->inboundReceiptService->markInboundPending(
                purchaseOrderId: $event->purchaseOrderId,
                procurementSessionId: $event->sessionId,
                correlationId: $event->correlationId
            );
        } catch (\Throwable $e) {
            Log::channel('fulfillment')->error("Failed to mark inbound pending on ProcurementCompleted for PO #{$event->purchaseOrderId}: {$e->getMessage()}");
        }
    }
}
