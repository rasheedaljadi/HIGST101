<?php

namespace Webkul\Fulfillment\Providers\CJ;

use Webkul\Fulfillment\Contracts\ExternalEventNormalizerInterface;
use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;

class CJEventNormalizer implements ExternalEventNormalizerInterface
{
    /**
     * Map CJ payload to NormalizedExternalEvent DTO.
     *
     * @param  array  $payload
     * @return \Webkul\Fulfillment\DataObjects\NormalizedExternalEvent
     */
    public function normalize(array $payload): NormalizedExternalEvent
    {
        return new NormalizedExternalEvent(
            eventId: $payload['cj_event_id'] ?? 'unknown_evt_id',
            externalSystem: 'cj',
            eventType: $payload['cj_status'] ?? 'unknown_event_type', // e.g. 'SHIPPED'
            resourceType: 'PurchaseOrder',
            resourceId: $payload['orderNo'] ?? 'unknown_resource_id',
            occurredAt: $payload['time'] ?? now()->toIso8601String(),
            receivedAt: now()->toIso8601String(),
            schemaVersion: $payload['v'] ?? '2.0',
            correlationId: $payload['corr_id'] ?? null,
            causationId: $payload['caus_id'] ?? null,
            attributes: [
                'tracking_number' => $payload['trackingNumber'] ?? null,
                'carrier'         => $payload['logisticsCompany'] ?? null,
                'reason'          => $payload['cancelReason'] ?? null,
            ]
        );
    }
}
