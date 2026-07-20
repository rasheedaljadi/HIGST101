<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Contracts\ExternalEventNormalizerInterface;
use Webkul\Fulfillment\Contracts\ExternalRetryPolicy;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Services\Domain\ExternalStateMapper;

class InboxEventProcessor
{
    public function __construct(protected ExternalStateMapper $stateMapper) {}

    /**
     * Process pending inbox events.
     *
     * @return int Number of processed events
     */
    public function processPending(): int
    {
        $processedCount = 0;
        $lockId = (string) Str::uuid();

        // 1. Pessimistic lock and update events to processing state
        $events = DB::transaction(function () use ($lockId) {
            $query = DB::table('external_inbox_events')
                ->where('status', 'pending');

            $rows = $query->lockForUpdate()->get();

            if ($rows->isEmpty()) {
                return $rows;
            }

            DB::table('external_inbox_events')
                ->whereIn('id', $rows->pluck('id')->toArray())
                ->update([
                    'status'                => 'processing',
                    'processing_started_at' => now(),
                    'processing_lock_id'    => $lockId,
                ]);

            return $rows;
        });

        foreach ($events as $event) {
            $provider = $event->provider;
            $payload = json_decode($event->payload, true);

            // Get retry policy from config to determine max attempts
            $policyClass = config("fulfillment.retry_policies.{$provider}");
            $maxAttempts = 3;
            if ($policyClass) {
                /** @var ExternalRetryPolicy $policy */
                $policy = app($policyClass);
                $maxAttempts = $policy->maxAttempts();
            }

            try {
                // 2. Normalization
                $normalizerClass = config("fulfillment.normalizers.{$provider}");
                if (! $normalizerClass) {
                    throw new \RuntimeException("No normalizer registered for provider: {$provider}");
                }

                /** @var ExternalEventNormalizerInterface $normalizer */
                $normalizer = app($normalizerClass);
                $normalizedEvent = $normalizer->normalize($payload);

                // Ensure correlation/causation tracking from raw inbox metadata if not present in normalizer
                if (empty($normalizedEvent->correlationId)) {
                    $normalizedEvent->correlationId = $event->provider . '-' . $event->event_id;
                }
                if (empty($normalizedEvent->causationId)) {
                    $normalizedEvent->causationId = $event->event_id;
                }

                // 3. State Mapping to intent action
                $actionDto = $this->stateMapper->map($normalizedEvent);

                // 4. Update Aggregate
                $poId = $event->aggregate_id ?: $normalizedEvent->resourceId;
                
                // Find purchase order
                $po = PurchaseOrder::where('id', $poId)
                    ->orWhere('external_order_id', $poId)
                    ->orWhere('internal_reference', $poId)
                    ->first();

                if (! $po) {
                    throw new \RuntimeException("PurchaseOrder aggregate not found for identifier: {$poId}");
                }

                // Apply action on aggregate within database transaction
                DB::transaction(function () use ($po, $actionDto) {
                    $session = \Webkul\Fulfillment\Models\ProcurementSession::where('procurement_aggregate_id', function ($query) use ($po) {
                        $query->select('id')->from('procurement_aggregates')->where('purchase_order_id', $po->id);
                    })->first();

                    switch ($actionDto->action) {
                        case 'MARK_SUBMITTED':
                            $po->submit(
                                $actionDto->attributes['external_order_id'] ?? $po->external_order_id ?? 'unknown',
                                $actionDto->attributes['raw_response'] ?? []
                            );
                            if ($session) {
                                $session->transitionTo('SUBMITTED');
                            }
                            break;

                        case 'MARK_AWAITING_PAYMENT':
                            $po->markAwaitingPayment();
                            if ($session) {
                                $session->transitionTo('WAITING_PAYMENT');
                            }
                            break;

                        case 'MARK_PROCESSING':
                            $po->markSupplierProcessing();
                            if ($session) {
                                $session->transitionTo('PROCESSING');
                            }
                            break;

                        case 'MARK_SHIPPED':
                            $po->markSupplierShipped(
                                $actionDto->attributes['tracking_number'] ?? 'unknown',
                                $actionDto->attributes['carrier'] ?? 'unknown'
                            );
                            if ($session) {
                                if (in_array($session->state, ['SUBMITTED', 'WAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'PROCESSING'], true)) {
                                    $session->state = 'PROCESSING';
                                }
                                $session->transitionTo('SHIPPED');
                            }
                            break;

                        case 'MARK_DELIVERED':
                            $po->markSupplierDelivered();
                            if ($session) {
                                if (in_array($session->state, ['SUBMITTED', 'WAITING_PAYMENT', 'PAYMENT_CONFIRMED', 'PROCESSING', 'SHIPPED'], true)) {
                                    $session->state = 'SHIPPED';
                                }
                                $session->transitionTo('COMPLETED');
                            }
                            break;

                        case 'MARK_CANCELED':
                            $po->cancel($actionDto->attributes['reason'] ?? 'Cancelled');
                            if ($session) {
                                $session->transitionTo('CANCELLED');
                            }
                            break;

                        case 'MARK_NEEDS_REVIEW':
                        default:
                            $po->markNeedsReview($actionDto->attributes['reason'] ?? 'Needs review.');
                            if ($session) {
                                $session->transitionTo('MANUAL_REVIEW');
                            }
                            break;
                    }
                });

                // Transition inbox event status to processed
                DB::table('external_inbox_events')
                    ->where('id', $event->id)
                    ->update([
                        'status'       => 'processed',
                        'attempts'     => $event->attempts + 1,
                        'processed_at' => now(),
                    ]);

                $processedCount++;

            } catch (\Exception $e) {
                Log::error("Inbox processing failed for event ID {$event->id}: " . $e->getMessage());

                $newAttempts = $event->attempts + 1;
                $newStatus = $newAttempts >= $maxAttempts ? 'dead_letter' : 'pending';

                DB::table('external_inbox_events')
                    ->where('id', $event->id)
                    ->update([
                        'status'     => $newStatus,
                        'attempts'   => $newAttempts,
                        'last_error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                    ]);
            }
        }

        return $processedCount;
    }

    /**
     * Recover inbox events that got stuck in 'processing' status due to crashes.
     *
     * @param  int  $timeoutSeconds
     * @return int Number of recovered events
     */
    public function recoverTimedOutEvents(int $timeoutSeconds = 300): int
    {
        $cutoff = now()->subSeconds($timeoutSeconds);

        return DB::table('external_inbox_events')
            ->where('status', 'processing')
            ->where('processing_started_at', '<=', $cutoff)
            ->update([
                'status'                => 'pending',
                'processing_lock_id'    => null,
                'processing_started_at' => null,
            ]);
    }
}
