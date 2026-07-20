<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Contracts\ExternalWebhookVerifierInterface;

class ExternalInboxService
{
    /**
     * Ingest a webhook request.
     *
     * @param  string  $provider External system code (e.g. 'aliexpress')
     * @param  string  $eventId Unique event ID from provider
     * @param  string  $eventType Webhook event type
     * @param  array  $payload Full raw request body
     * @param  \Illuminate\Http\Request  $request Request for signature validation
     * @return array [status => success|duplicate|invalid_signature, record_id => int|null]
     */
    public function ingest(string $provider, string $eventId, string $eventType, array $payload, Request $request): array
    {
        // 1. Verify Signature if verifier is configured
        $verifierClass = config("fulfillment.verifiers.{$provider}");
        if ($verifierClass) {
            /** @var ExternalWebhookVerifierInterface $verifier */
            $verifier = app($verifierClass);
            if (! $verifier->verify($request)) {
                return ['status' => 'invalid_signature', 'record_id' => null];
            }
        }

        // 2. Perform Deduplication check
        $exists = DB::table('external_inbox_events')
            ->where('provider', $provider)
            ->where('event_id', $eventId)
            ->exists();

        if ($exists) {
            return ['status' => 'duplicate', 'record_id' => null];
        }

        // 3. Persist Event to Inbox in pending status
        $id = DB::table('external_inbox_events')->insertGetId([
            'provider'             => $provider,
            'event_id'             => $eventId,
            'event_type'           => $eventType,
            'aggregate_type'       => $payload['aggregate_type'] ?? null,
            'aggregate_id'         => $payload['aggregate_id'] ?? null,
            'payload'              => json_encode($payload),
            'signature'            => $request->header('X-Signature') ?: $request->header('Signature'),
            'status'               => 'pending',
            'attempts'             => 0,
            'last_error'           => null,
            'processing_started_at'=> null,
            'processing_lock_id'   => null,
            'received_at'          => now(),
            'processed_at'         => null,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return ['status' => 'success', 'record_id' => $id];
    }
}
