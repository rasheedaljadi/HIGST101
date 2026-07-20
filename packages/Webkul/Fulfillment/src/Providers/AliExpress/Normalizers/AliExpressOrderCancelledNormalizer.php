<?php

namespace Webkul\Fulfillment\Providers\AliExpress\Normalizers;

use Webkul\Fulfillment\DataObjects\NormalizedExternalEvent;

class AliExpressOrderCancelledNormalizer
{
    public function normalize(array $payload): NormalizedExternalEvent
    {
        return new NormalizedExternalEvent(
            eventId: $payload['event_id'] ?? uniqid('evt-', true),
            externalSystem: 'aliexpress',
            eventType: 'order_cancelled',
            resourceType: 'ExternalOrder',
            resourceId: $payload['aliexpress_order_id'] ?? $payload['order_id'] ?? 'unknown',
            occurredAt: $payload['timestamp'] ?? now()->toIso8601String(),
            receivedAt: now()->toIso8601String(),
            schemaVersion: '1.0',
            correlationId: $payload['correlation_id'] ?? null,
            causationId: $payload['causation_id'] ?? null,
            attributes: [
                'status' => 'CANCELLED',
                'reason' => $payload['reason'] ?? 'cancelled by supplier',
            ]
        );
    }
}
