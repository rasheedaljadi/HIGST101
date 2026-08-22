<?php

namespace Webkul\Procurement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\Models\AliExpressWebhookInboxMessage;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementAuditLog;
use Webkul\Procurement\Models\ProcurementDemandAllocation;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\AliExpressPollingService;

class ProcessAliExpressWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public int $inboxMessageId
    ) {}

    /**
     * Execute the webhook processing job.
     */
    public function handle(
        AliExpressOrderGateway $orderGateway,
        AliExpressPollingService $pollingService
    ): void {
        /** @var AliExpressWebhookInboxMessage $inboxMessage */
        $inboxMessage = AliExpressWebhookInboxMessage::find($this->inboxMessageId);

        if (! $inboxMessage) {
            Log::channel('aliexpress')->warning("ProcessAliExpressWebhookJob: Inbox message #{$this->inboxMessageId} not found.");

            return;
        }

        if (in_array($inboxMessage->status, [
            AliExpressWebhookInboxMessage::STATUS_PROCESSED,
            AliExpressWebhookInboxMessage::STATUS_IGNORED,
        ], true)) {
            return;
        }

        $inboxMessage->increment('attempts');
        $inboxMessage->update(['status' => AliExpressWebhookInboxMessage::STATUS_PROCESSING]);

        $eventType = (int) $inboxMessage->event_type;
        $allowedTypes = [53, 51, 18, 65];

        // 1. Enforce Allowlist of event types
        if (! in_array($eventType, $allowedTypes, true)) {
            $inboxMessage->update([
                'status' => AliExpressWebhookInboxMessage::STATUS_IGNORED,
                'failure_code' => 'UNSUBSCRIBED_OR_NON_V2_EVENT_TYPE',
                'processed_at' => now(),
            ]);

            Log::channel('aliexpress')->info("ProcessAliExpressWebhookJob: Ignored event type {$eventType} for message #{$inboxMessage->id}.");

            return;
        }

        // 2. Handle System Notification (Type 65: Authorization Expiration)
        if ($eventType === 65) {
            ProcurementAuditLog::create([
                'auditable_type' => AliExpressWebhookInboxMessage::class,
                'auditable_id' => $inboxMessage->id,
                'action' => 'aliexpress_oauth_expiration_warning',
                'actor_id' => null,
                'actor_type' => 'webhook',
                'details' => [
                    'message' => 'AliExpress OAuth Access Token expiration notice received.',
                    'occurred_at' => $inboxMessage->occurred_at?->toIso8601String(),
                ],
                'correlation_id' => "wh-oauth-{$inboxMessage->id}",
            ]);

            $inboxMessage->update([
                'status' => AliExpressWebhookInboxMessage::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);

            return;
        }

        // 3. Handle Order Lifecycle & Tracking Events (Types 53, 51, 18)
        $tradeOrderId = $inboxMessage->external_order_id;
        if (empty($tradeOrderId)) {
            $payloadData = $inboxMessage->payload['data'] ?? [];
            $tradeOrderId = (string) ($payloadData['trade_order_id'] ?? $payloadData['order_id'] ?? '');
        }

        if (empty($tradeOrderId) || ! ctype_digit((string) $tradeOrderId)) {
            $inboxMessage->update([
                'status' => AliExpressWebhookInboxMessage::STATUS_IGNORED,
                'failure_code' => 'INVALID_OR_MISSING_NUMERIC_ORDER_ID',
                'processed_at' => now(),
            ]);

            Log::channel('aliexpress')->info("ProcessAliExpressWebhookJob: Ignored non-numeric order ID '{$tradeOrderId}' for message #{$inboxMessage->id}.");

            return;
        }

        /** @var ExternalPlatformOrder|null $platformOrder */
        $platformOrder = ExternalPlatformOrder::where('external_order_id', (string) $tradeOrderId)->first();

        if (! $platformOrder) {
            $inboxMessage->update([
                'status' => AliExpressWebhookInboxMessage::STATUS_IGNORED,
                'failure_code' => 'UNMATCHED_EXTERNAL_ORDER_ID',
                'processed_at' => now(),
            ]);

            Log::channel('aliexpress')->info("ProcessAliExpressWebhookJob: No matching ExternalPlatformOrder for order ID {$tradeOrderId}.");

            return;
        }

        // 4. Webhook-Pull Pairing: Authoritative Query via Gateway
        try {
            $snapshot = $orderGateway->getOrder((string) $tradeOrderId, $platformOrder->provider_account_id);
        } catch (\Throwable $e) {
            $inboxMessage->update([
                'failure_code' => 'AUTHORITATIVE_QUERY_EXCEPTION',
                'failure_message' => substr($e->getMessage(), 0, 500),
            ]);

            throw $e; // Trigger job retry
        }

        if ($snapshot->orderStatus === 'QUERY_FAILED' || $snapshot->orderStatus === 'TRANSPORT_ERROR') {
            $inboxMessage->update([
                'failure_code' => 'QUERY_FAILED',
                'failure_message' => 'AliExpress authoritative query failed to retrieve status.',
            ]);

            throw new \RuntimeException("Authoritative query failed for order ID {$tradeOrderId}: {$snapshot->rawStatus}");
        }

        // 5. Monotonic State Synchronization
        $syncPayload = [
            'status' => $snapshot->orderStatus,
            'tracking_number' => $snapshot->trackingNumber,
            'carrier' => $snapshot->carrierName,
            'provider_updated_at' => now()->toIso8601String(),
        ];

        DB::transaction(function () use ($platformOrder, $syncPayload, $pollingService) {
            $updatedOrder = $pollingService->syncOrder($platformOrder, $syncPayload);

            // Handle Allocation Release if Cancelled/Closed
            if ($updatedOrder->normalized_status === ExternalPlatformOrder::STATUS_CANCELLED) {
                /** @var SupplierPurchaseOrder|null $spo */
                $spo = SupplierPurchaseOrder::with('items')->find($platformOrder->supplier_purchase_order_id);
                if ($spo && $spo->items) {
                    $itemIds = $spo->items->pluck('id')->toArray();
                    if (! empty($itemIds)) {
                        ProcurementDemandAllocation::whereIn('supplier_purchase_order_item_id', $itemIds)
                            ->where('state', 'allocated')
                            ->update([
                                'state' => 'cancelled',
                                'qty_cancelled' => DB::raw('qty_allocated'),
                                'qty_allocated' => 0,
                            ]);
                    }
                }
            }
        });

        $inboxMessage->update([
            'status' => AliExpressWebhookInboxMessage::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }
}
