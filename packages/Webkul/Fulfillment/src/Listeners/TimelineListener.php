<?php

namespace Webkul\Fulfillment\Listeners;

use Webkul\Fulfillment\Models\FinancialTimeline;
use Webkul\Fulfillment\Repositories\FinancialTimelineRepository;

class TimelineListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(protected FinancialTimelineRepository $timelineRepository) {}

    /**
     * Handle the event and append to the FinancialTimeline log.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @param  string  $correlationId
     * @param  string  $causationId
     * @return void
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        // 1. Idempotency Check: check if event has already been recorded
        $existing = $this->timelineRepository->findWhere(['event_id' => $outboxEventId])->first();
        if ($existing) {
            return;
        }

        $orderId = $payload['order_id'] ?? null;
        $amount = isset($payload['quantity']) ? ($payload['quantity'] * 20.00) : 100.00;

        $eventTypeMap = [
            'OrderAllocationReserved' => 'allocation_reserved',
            'OrderAllocationReleased' => 'allocation_released',
            'PurchaseOrderCreated'    => 'purchase_order_created',
            'SupplierOrderSubmitted'  => 'supplier_order_submitted',
            'SupplierOrderFailed'     => 'supplier_order_failed',
        ];

        $eventType = $eventTypeMap[$eventName] ?? strtolower($eventName);

        $metadata = [
            'correlation_id' => $correlationId,
            'causation_id'   => $causationId,
            'details'        => $payload,
        ];

        // Pass outboxEventId as the stable event_id
        $timeline = FinancialTimeline::appendEvent($orderId, $eventType, $amount, 'USD', $metadata, $outboxEventId);
        
        // Save using repository
        $this->timelineRepository->create($timeline->toArray());
    }
}
