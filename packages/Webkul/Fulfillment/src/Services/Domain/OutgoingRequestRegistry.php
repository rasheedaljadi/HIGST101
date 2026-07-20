<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\Models\OutgoingRequest;

class OutgoingRequestRegistry
{
    /**
     * Check if a request has already been sent and completed.
     */
    public function findRequest(string $requestHash, string $idempotencyKey): ?OutgoingRequest
    {
        return OutgoingRequest::where('request_hash', $requestHash)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * Record a new request entry.
     */
    public function recordRequest(
        string $requestHash,
        string $endpoint,
        string $idempotencyKey,
        ?array $responsePayload = null,
        ?string $responseHash = null
    ): OutgoingRequest {
        return OutgoingRequest::create([
            'request_hash'     => $requestHash,
            'endpoint'         => $endpoint,
            'idempotency_key'  => $idempotencyKey,
            'response_payload' => $responsePayload,
            'response_hash'    => $responseHash,
            'sent_at'          => now(),
        ]);
    }
}
