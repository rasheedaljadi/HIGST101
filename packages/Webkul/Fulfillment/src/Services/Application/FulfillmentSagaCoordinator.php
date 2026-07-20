<?php

namespace Webkul\Fulfillment\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Fulfillment\Commands\CancelAllocationCommand;
use Webkul\Fulfillment\Exceptions\FulfillmentSagaException;
use Webkul\Fulfillment\Handlers\CancelAllocationHandler;
use Webkul\Fulfillment\Services\Domain\EventDeduplicationService;
use Webkul\Fulfillment\Services\Domain\FulfillmentDecisionService;

class FulfillmentSagaCoordinator
{
    /**
     * Create a new coordinator instance.
     */
    public function __construct(
        protected EventDeduplicationService $deduplicationService,
        protected FulfillmentDecisionService $decisionService,
        protected LocalFulfillmentWorkflow $localWorkflow,
        protected SupplierProcurementWorkflow $supplierWorkflow,
        protected CancelAllocationHandler $cancelHandler
    ) {}

    /**
     * Coordinate fulfillment saga.
     *
     * @param  int  $orderId
     * @param  int  $orderItemId
     * @param  int  $qty
     * @param  string  $eventId
     * @param  string  $correlationId
     * @param  array  $decisionContext
     * @return array
     */
    public function coordinate(int $orderId, int $orderItemId, int $qty, string $eventId, string $correlationId, array $decisionContext = []): array
    {
        $causationId = $eventId;

        // 1. Idempotency Check using Deduplication Service
        $dedupResult = $this->deduplicationService->processEvent(
            provider: 'saga_coordinator',
            eventId: $eventId,
            eventName: 'FulfillmentSagaInitiated',
            occurredAt: now()
        );

        if (! $dedupResult->isAccepted()) {
            return [
                'status' => $dedupResult->getStatus(),
                'data'   => null,
            ];
        }

        // 2. Evaluate Routing Policy
        $decision = $this->decisionService->makeDecision($orderId, $decisionContext);

        // 3. Coordinate based on Decision
        try {
            if ($decision->isLocal()) {
                $allocation = $this->localWorkflow->processLocal($orderId, $orderItemId, $qty, $correlationId, $causationId);
                
                return [
                    'status' => 'success',
                    'data'   => ['allocation' => $allocation],
                ];
            } else {
                [$allocation, $po] = $this->supplierWorkflow->processSupplier(
                    $orderId,
                    $orderItemId,
                    $qty,
                    $decision->provider ?? 'aliexpress',
                    $correlationId,
                    $causationId
                );

                return [
                    'status' => 'success',
                    'data'   => ['allocation' => $allocation, 'purchase_order' => $po],
                ];
            }
        } catch (FulfillmentSagaException $e) {
            // Supplier failure occurred -> Trigger Compensation Flow inside transaction block
            $failedPoId = null;
            $failedAllocationId = null;

            // Extract IDs from exception or context if possible (our SupplierProcurementWorkflow logs failed PO event)
            $previousEx = $e->getPrevious();

            // Run compensation steps
            DB::transaction(function () use ($orderId, $correlationId, $causationId, $e) {
                // Flag OrderProcess for Operations review
                $process = \Webkul\Fulfillment\Models\OrderProcess::where('order_id', $orderId)->first();
                if ($process) {
                    $process->flagOps('Supplier procurement failed: ' . $e->getMessage());
                }

                // Find draft allocations to release
                $allocationsToCancel = \Webkul\Fulfillment\Models\OrderAllocation::where('order_id', $orderId)
                    ->where('state', 'reserved')
                    ->get();

                foreach ($allocationsToCancel as $alloc) {
                    $cancelCommand = new CancelAllocationCommand(
                        allocationId: $alloc->id,
                        quantity: $alloc->reserved_qty,
                        reason: 'Supplier procurement failure compensation',
                        correlationId: $correlationId,
                        causationId: $causationId
                    );
                    $this->cancelHandler->handle($cancelCommand);
                }

                // Transition Customer Order status / Emit CustomerOrderFlagged domain event
                DB::table('domain_outbox_events')->insert([
                    'event_id'       => (string) Str::uuid(),
                    'event_name'     => 'CustomerOrderFlagged',
                    'event_version'  => 1,
                    'aggregate_type' => 'Order',
                    'aggregate_id'   => (string) $orderId,
                    'correlation_id' => $correlationId,
                    'causation_id'   => $causationId,
                    'payload'        => json_encode([
                        'order_id' => $orderId,
                        'reason'   => 'Supplier procurement failed. Order flagged for manual review.',
                    ]),
                    'status'         => 'pending',
                    'attempts'       => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            return [
                'status' => 'compensated',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
