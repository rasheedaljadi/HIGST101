<?php

namespace Webkul\Procurement\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Events\OrderAccepted;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Sales\Repositories\OrderRepository;

class OrderAcceptedListener
{
    public function __construct(
        protected ProcurementDemandService $demandService,
        protected OrderRepository $orderRepository
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAccepted $event): void
    {
        if (! config('procurement.v2_enabled', false)) {
            return;
        }

        $order = $this->orderRepository->find($event->orderId);

        if (! $order) {
            Log::warning("[Procurement V2] Order #{$event->orderId} not found when handling OrderAccepted event.");

            return;
        }

        Log::info("[Procurement V2] Processing Order #{$order->id} for demand generation.");
        $this->demandService->processOrderDemands($order);
    }
}
