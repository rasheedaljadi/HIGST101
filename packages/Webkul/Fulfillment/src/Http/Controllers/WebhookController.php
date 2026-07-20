<?php

namespace Webkul\Fulfillment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Fulfillment\Services\Application\ExternalInboxService;
use Webkul\Fulfillment\Services\Application\InboxEventProcessor;

class WebhookController extends Controller
{
    /**
     * Handle incoming AliExpress dropshipping webhook.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request, string $provider)
    {
        $payload = $request->json()->all();
        $eventId = $request->header('X-Event-ID') ?: ($payload['event_id'] ?? null);
        $eventType = $request->header('X-Event-Type') ?: ($payload['event_type'] ?? null);

        if (!$eventId || !$eventType) {
            return response()->json(['error' => 'Missing event metadata'], 400);
        }

        /** @var ExternalInboxService $inboxService */
        $inboxService = app(ExternalInboxService::class);

        // 1. Validate signature & timestamp and Persist inside transaction boundary (handled in ingest)
        $res = $inboxService->ingest($provider, $eventId, $eventType, $payload, $request);

        if ($res['status'] === 'invalid_signature') {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if ($res['status'] === 'duplicate') {
            return response()->json(['status' => 'duplicate_ignored'], 200);
        }

        // 2. Transaction has been fully committed inside ingest() on success.
        // Process the event. If processing fails, catch the error, keep the event status as pending/failed in the DB,
        // and STILL return HTTP 200 to prevent AliExpress from redundant retries.
        if ($res['status'] === 'success') {
            try {
                /** @var InboxEventProcessor $inboxProcessor */
                $inboxProcessor = app(InboxEventProcessor::class);
                $inboxProcessor->processPending();
            } catch (\Throwable $e) {
                Log::channel('aliexpress')->error('Webhook real-time processing failed: ' . $e->getMessage(), [
                    'event_id' => $eventId,
                    'trace'    => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json([
            'status'    => 'success',
            'record_id' => $res['record_id']
        ], 200);
    }
}
