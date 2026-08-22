<?php

namespace App\Http\Controllers\AliExpress;

use App\Http\Controllers\Controller;
use App\Services\AliExpress\AliExpressWebhookSignatureVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Jobs\ProcessAliExpressWebhookJob;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;

class AliExpressWebhookController extends Controller
{
    public function __construct(
        protected AliExpressWebhookSignatureVerifier $signatureVerifier
    ) {}

    /**
     * Handle incoming Message Push service (Webhook / GOP callback) from AliExpress Open Platform.
     */
    public function handle(Request $request): Response
    {
        // 1. If simple GET liveness check, return immediate 200 OK
        if ($request->isMethod('get')) {
            return response('{"code":0,"message":"AliExpress Message Push Service endpoint is alive."}', 200)
                ->header('Content-Type', 'application/json');
        }

        // 2. Strict Signature Verification
        if (! $this->signatureVerifier->verify($request)) {
            return response('{"code":401,"message":"Unauthorized: Invalid or missing webhook signature."}', 401)
                ->header('Content-Type', 'application/json');
        }

        $rawBody = (string) $request->getContent();
        $data = json_decode($rawBody, true) ?? [];

        $eventType = (int) ($data['message_type'] ?? 0);
        $externalEventId = isset($data['event_id']) ? (string) $data['event_id'] : null;
        $externalOrderId = isset($data['data']['trade_order_id'])
            ? (string) $data['data']['trade_order_id']
            : (isset($data['data']['order_id']) ? (string) $data['data']['order_id'] : null);

        $payloadHash = hash('sha256', $rawBody);
        $fingerprint = AliExpressWebhookInboxMessage::computeFingerprint(
            provider: 'aliexpress',
            eventType: $eventType,
            externalEventId: $externalEventId,
            externalOrderId: $externalOrderId,
            payloadHash: $payloadHash
        );

        $occurredAt = null;
        if (! empty($data['timestamp']) && is_numeric($data['timestamp'])) {
            $occurredAt = date('Y-m-d H:i:s', (int) $data['timestamp']);
        } elseif (! empty($data['data']['status_update_time'])) {
            $occurredAt = date('Y-m-d H:i:s', strtotime((string) $data['data']['status_update_time']));
        }

        // 3. Persistent Idempotent Inbox Insertion
        $inboxMessage = null;
        $isNew = false;

        try {
            DB::transaction(function () use (
                &$inboxMessage,
                &$isNew,
                $eventType,
                $externalEventId,
                $externalOrderId,
                $payloadHash,
                $fingerprint,
                $data,
                $occurredAt
            ) {
                $existing = AliExpressWebhookInboxMessage::where('fingerprint', $fingerprint)->first();

                if ($existing) {
                    $inboxMessage = $existing;
                    $isNew = false;

                    return;
                }

                $inboxMessage = AliExpressWebhookInboxMessage::create([
                    'provider' => 'aliexpress',
                    'event_type' => $eventType,
                    'external_event_id' => $externalEventId,
                    'external_order_id' => $externalOrderId,
                    'payload_hash' => $payloadHash,
                    'fingerprint' => $fingerprint,
                    'payload' => $data,
                    'occurred_at' => $occurredAt,
                    'received_at' => now(),
                    'status' => AliExpressWebhookInboxMessage::STATUS_RECEIVED,
                    'attempts' => 0,
                ]);

                $isNew = true;

                DB::afterCommit(function () use ($inboxMessage) {
                    ProcessAliExpressWebhookJob::dispatch($inboxMessage->id);
                });
            });
        } catch (QueryException $e) {
            // Handle race condition on duplicate unique fingerprint key gracefully
            if ($e->errorInfo[1] == 1062 || str_contains($e->getMessage(), 'Duplicate entry')) {
                Log::channel('aliexpress')->info("AliExpress Webhook duplicate race ignored for fingerprint {$fingerprint}.");
            } else {
                Log::channel('aliexpress')->error('AliExpress Webhook inbox insertion error: '.$e->getMessage());
            }
        }

        // 4. Return immediate 200 OK acknowledgment (<500ms requirement)
        $responseContent = ! empty($rawBody) ? $rawBody : '{"code":0,"message":"success"}';

        return response($responseContent, 200)
            ->header('Content-Type', 'application/json');
    }
}
