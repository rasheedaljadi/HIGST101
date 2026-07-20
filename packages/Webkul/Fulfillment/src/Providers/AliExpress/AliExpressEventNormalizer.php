<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Webkul\Fulfillment\Contracts\ExternalEventNormalizerInterface;
use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;
use Webkul\Fulfillment\Providers\AliExpress\Normalizers\AliExpressOrderCreatedNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\Normalizers\AliExpressOrderPaidNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\Normalizers\AliExpressOrderShippedNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\Normalizers\AliExpressOrderCancelledNormalizer;
use Webkul\Fulfillment\Providers\AliExpress\Normalizers\AliExpressOrderDeliveredNormalizer;

class AliExpressEventNormalizer implements ExternalEventNormalizerInterface
{
    public function normalize(array $payload): NormalizedExternalEvent
    {
        $status = strtolower($payload['status'] ?? $payload['event_name'] ?? '');

        switch ($status) {
            case 'place_order_success':
            case 'order_created':
                return (new AliExpressOrderCreatedNormalizer())->normalize($payload);
            case 'payment_success':
            case 'order_paid':
                return (new AliExpressOrderPaidNormalizer())->normalize($payload);
            case 'wait_receive':
            case 'shipped':
            case 'order_shipped':
            case 'seller_send_goods':
                return (new AliExpressOrderShippedNormalizer())->normalize($payload);
            case 'cancelled':
            case 'closed':
            case 'order_cancelled':
                return (new AliExpressOrderCancelledNormalizer())->normalize($payload);
            case 'finish':
            case 'completed':
            case 'order_delivered':
                return (new AliExpressOrderDeliveredNormalizer())->normalize($payload);
        }

        return new NormalizedExternalEvent(
            eventId: $payload['event_id'] ?? 'unknown_evt_id',
            externalSystem: 'aliexpress',
            eventType: $status ?: 'unknown_event_type',
            resourceType: 'PurchaseOrder',
            resourceId: $payload['order_id'] ?? 'unknown_resource_id',
            occurredAt: $payload['timestamp'] ?? now()->toIso8601String(),
            receivedAt: now()->toIso8601String(),
            schemaVersion: $payload['schema_version'] ?? '1.0',
            correlationId: $payload['correlation_id'] ?? null,
            causationId: $payload['causation_id'] ?? null,
            attributes: [
                'tracking_number' => $payload['tracking_number'] ?? null,
                'carrier'         => $payload['carrier_code'] ?? null,
                'reason'          => $payload['reason'] ?? null,
                'error_message'   => $payload['error_message'] ?? null,
            ]
        );
    }
}
