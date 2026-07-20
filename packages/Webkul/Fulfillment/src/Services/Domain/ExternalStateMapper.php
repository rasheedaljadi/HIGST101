<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;
use Webkul\Fulfillment\DataObjects\PurchaseOrderAction;

class ExternalStateMapper
{
    /**
     * Map a normalized external event to a purchase order action.
     *
     * @param  \Webkul\Fulfillment\DataObjects\NormalizedExternalEvent  $event
     * @return \Webkul\Fulfillment\DataObjects\PurchaseOrderAction
     */
    public function map(NormalizedExternalEvent $event): PurchaseOrderAction
    {
        $eventType = strtoupper($event->eventType);
        $attributes = $event->attributes;

        switch ($eventType) {
            case 'ORDER_SUBMITTED':
            case 'ORDER_CREATED':
                return new PurchaseOrderAction('MARK_SUBMITTED', [
                    'external_order_id' => $event->resourceId,
                    'raw_response'      => $attributes,
                ]);

            case 'ORDER_AWAITING_PAYMENT':
                return new PurchaseOrderAction('MARK_AWAITING_PAYMENT');

            case 'ORDER_PROCESSING':
            case 'SELLER_PROCESSING_GOODS':
                return new PurchaseOrderAction('MARK_PROCESSING');

            case 'ORDER_SHIPPED':
            case 'SELLER_SEND_GOODS':
            case 'SHIPPED':
                return new PurchaseOrderAction('MARK_SHIPPED', [
                    'tracking_number' => $attributes['tracking_number'] ?? null,
                    'carrier'         => $attributes['carrier'] ?? null,
                ]);

            case 'ORDER_DELIVERED':
            case 'BUYER_ACCEPT_GOODS':
            case 'DELIVERED':
                return new PurchaseOrderAction('MARK_DELIVERED');

            case 'ORDER_CANCELLED':
            case 'CANCELLED':
            case 'ORDER_CLOSED':
                return new PurchaseOrderAction('MARK_CANCELED', [
                    'reason' => $attributes['reason'] ?? 'Cancelled by external supplier.',
                ]);

            case 'ORDER_FAILED':
            case 'FAILED':
            case 'PAYMENT_FAILED':
            case 'STOCK_OUT':
                return new PurchaseOrderAction('MARK_NEEDS_REVIEW', [
                    'reason' => $attributes['error_message'] ?? 'Failed during external processing.',
                ]);

            default:
                return new PurchaseOrderAction('MARK_NEEDS_REVIEW', [
                    'reason' => "Unhandled external event type: {$eventType}",
                ]);
        }
    }
}
