<?php

namespace Webkul\DeliveryManagement\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAttemptLog;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Models\Order as OrderModel;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class DeliveryLifecycleService
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Assign order delivery to a specific courier.
     *
     * @throws Exception
     */
    public function assignToCourier(
        DeliveryAssignment $assignment,
        int $courierId,
        int $supervisorId
    ): DeliveryAssignment {
        if (! in_array($assignment->status, [
            DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
            DeliveryAssignment::STATUS_ASSIGNED,
            DeliveryAssignment::STATUS_RETRY_SCHEDULED,
        ], true)) {
            throw new Exception("Cannot assign courier when assignment is in '{$assignment->status}' state.");
        }

        $assignment->update([
            'delivery_boy_id' => $courierId,
            'assigned_by' => $supervisorId,
            'assigned_at' => now(),
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
        ]);

        return $assignment->fresh();
    }

    /**
     * Assign order delivery to a specific delivery point.
     *
     * @throws Exception
     */
    public function assignToDeliveryPoint(
        DeliveryAssignment $assignment,
        int $deliveryPointId,
        int $supervisorId
    ): DeliveryAssignment {
        if (! in_array($assignment->status, [
            DeliveryAssignment::STATUS_READY_FOR_ASSIGNMENT,
            DeliveryAssignment::STATUS_ASSIGNED,
        ], true)) {
            throw new Exception("Cannot assign delivery point when assignment is in '{$assignment->status}' state.");
        }

        $assignment->update([
            'delivery_point_id' => $deliveryPointId,
            'assigned_by' => $supervisorId,
            'assigned_at' => now(),
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
        ]);

        return $assignment->fresh();
    }

    /**
     * Courier starts delivery journey to customer.
     *
     * @throws Exception
     */
    public function startDelivery(
        DeliveryAssignment $assignment,
        int $courierId
    ): DeliveryAssignment {
        if ($assignment->delivery_boy_id !== $courierId) {
            throw new Exception('Unauthorized: Courier does not own this assignment.');
        }

        if (! in_array($assignment->status, [
            DeliveryAssignment::STATUS_PICKED_UP,
            DeliveryAssignment::STATUS_RETRY_SCHEDULED,
            DeliveryAssignment::STATUS_ASSIGNED,
        ], true)) {
            throw new Exception("Cannot start delivery from status '{$assignment->status}'.");
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            'out_for_delivery_at' => now(),
        ]);

        return $assignment->fresh();
    }

    /**
     * Confirm package arrival at pickup point.
     *
     * @throws Exception
     */
    public function confirmArrivalAtPoint(
        DeliveryAssignment $assignment,
        int $actorId,
        ?int $deliveryPointId = null
    ): DeliveryAssignment {
        if ($deliveryPointId && $assignment->delivery_point_id && $assignment->delivery_point_id !== $deliveryPointId) {
            throw new Exception('Unauthorized: Delivery point mismatch.');
        }

        if (! in_array($assignment->status, [
            DeliveryAssignment::STATUS_PICKED_UP,
            DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            DeliveryAssignment::STATUS_ASSIGNED,
        ], true)) {
            throw new Exception("Cannot confirm arrival at point from status '{$assignment->status}'.");
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_ARRIVED_AT_POINT,
            'notes' => ($assignment->notes ? $assignment->notes."\n" : '')."Package confirmed arrived at pickup point by User #{$actorId} at ".now()->toIso8601String(),
        ]);

        return $assignment->fresh();
    }

    /**
     * Final confirmation of delivery to customer and COD cash collection.
     *
     * @throws Exception
     */
    public function confirmCustomerDelivery(
        DeliveryAssignment $assignment,
        int $actorId,
        string $actorType = 'courier',
        ?float $collectedAmount = null,
        string $currency = 'YER',
        ?string $idempotencyKey = null
    ): DeliveryAssignment {
        return DB::transaction(function () use ($assignment, $actorId, $actorType, $collectedAmount, $currency, $idempotencyKey) {
            /** @var DeliveryAssignment $assignment */
            $assignment = DeliveryAssignment::where('id', $assignment->id)->lockForUpdate()->first();

            // 1. Idempotency guard
            if ($assignment->status === DeliveryAssignment::STATUS_DELIVERED) {
                return $assignment;
            }

            if (! in_array($assignment->status, [
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
                DeliveryAssignment::STATUS_ARRIVED_AT_POINT,
                DeliveryAssignment::STATUS_PICKED_UP,
            ], true)) {
                throw new Exception("Cannot complete delivery from status '{$assignment->status}'.");
            }

            /** @var Order|null $order */
            $order = $this->orderRepository->find($assignment->order_id);

            if (! $order) {
                throw new Exception("Order #{$assignment->order_id} not found.");
            }

            $isCod = strtolower((string) $order->payment?->method) === 'cashondelivery';

            // 2. COD Cash Collection
            if ($isCod) {
                $expectedAmount = (float) $order->grand_total;
                $actualAmount = $collectedAmount !== null ? (float) $collectedAmount : $expectedAmount;

                if ($actualAmount < $expectedAmount) {
                    throw new Exception("Cash collection amount ({$actualAmount} {$currency}) is less than order grand total ({$expectedAmount} {$order->order_currency_code}).");
                }

                // Check existing cash collection to prevent duplicate collection
                $existingCollection = DeliveryCashCollection::where('delivery_assignment_id', $assignment->id)->first();

                if (! $existingCollection) {
                    $collectionKey = $idempotencyKey ?: ('COD-COLL-'.$assignment->id.'-'.$order->id);

                    DeliveryCashCollection::create([
                        'delivery_assignment_id' => $assignment->id,
                        'order_id' => $order->id,
                        'delivery_boy_id' => $assignment->delivery_boy_id ?: $actorId,
                        'amount' => $actualAmount,
                        'currency' => 'YER',
                        'base_currency' => core()->getBaseCurrencyCode() ?: 'YER',
                        'exchange_rate' => 1.0,
                        'amount_in_base_currency' => $actualAmount,
                        'rate_snapshot_at' => now(),
                        'collected_at' => now(),
                        'idempotency_key' => $collectionKey,
                    ]);
                }

                // Create paid invoice for COD order if not already created
                if (! $order->invoices()->exists()) {
                    $invoiceItems = [];
                    foreach ($order->items as $item) {
                        if ($item->qty_to_invoice > 0) {
                            $invoiceItems[$item->id] = $item->qty_to_invoice;
                        }
                    }

                    if (! empty($invoiceItems)) {
                        $this->invoiceRepository->create([
                            'order_id' => $order->id,
                            'invoice' => [
                                'items' => $invoiceItems,
                            ],
                        ], 'paid');
                    }
                }
            }

            // 3. Update DeliveryAssignment status to delivered
            $assignment->update([
                'status' => DeliveryAssignment::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            // 4. Update order status to completed if fully shipped and invoiced
            $order->refresh();
            if ($order->canInvoice() == false && $order->canShip() == false) {
                $this->orderRepository->updateOrderStatus($order, OrderModel::STATUS_COMPLETED);
            }

            Log::channel('delivery')->info("Delivery confirmed for Order #{$order->id}, Assignment #{$assignment->id} by Actor #{$actorId} ({$actorType})");

            return $assignment->fresh();
        });
    }

    /**
     * Record a delivery attempt failure and schedule retry or mark failed.
     *
     * @throws Exception
     */
    public function recordDeliveryFailure(
        DeliveryAssignment $assignment,
        string $reason,
        int $actorId,
        bool $scheduleRetry = true
    ): DeliveryAssignment {
        return DB::transaction(function () use ($assignment, $reason, $actorId, $scheduleRetry) {
            /** @var DeliveryAssignment $assignment */
            $assignment = DeliveryAssignment::where('id', $assignment->id)->lockForUpdate()->first();

            $newAttemptCount = $assignment->attempt_count + 1;
            $maxAttempts = $assignment->max_attempts ?: 3;

            // Log attempt
            DeliveryAttemptLog::create([
                'delivery_assignment_id' => $assignment->id,
                'attempt_number' => $newAttemptCount,
                'status' => 'failed',
                'failure_reason' => $reason,
                'attempted_at' => now(),
                'attempted_by' => $actorId,
            ]);

            if ($scheduleRetry && $newAttemptCount < $maxAttempts) {
                $status = DeliveryAssignment::STATUS_RETRY_SCHEDULED;
            } else {
                $status = DeliveryAssignment::STATUS_DELIVERY_FAILED;
            }

            $assignment->update([
                'attempt_count' => $newAttemptCount,
                'status' => $status,
                'failure_reason' => $reason,
                'failed_at' => $status === DeliveryAssignment::STATUS_DELIVERY_FAILED ? now() : null,
            ]);

            return $assignment->fresh();
        });
    }

    /**
     * Return package to central warehouse (hayest_central) with supervisor permission and restore physical stock once.
     *
     * @throws Exception
     */
    public function returnToHayest(
        DeliveryAssignment $assignment,
        int $supervisorId,
        string $reason,
        ?string $idempotencyKey = null
    ): DeliveryAssignment {
        return DB::transaction(function () use ($assignment, $supervisorId, $reason) {
            /** @var DeliveryAssignment $assignment */
            $assignment = DeliveryAssignment::where('id', $assignment->id)->lockForUpdate()->first();

            // 1. Idempotency guard
            if ($assignment->status === DeliveryAssignment::STATUS_RETURNED_TO_HAYEST) {
                return $assignment;
            }

            if (! in_array($assignment->status, [
                DeliveryAssignment::STATUS_DELIVERY_FAILED,
                DeliveryAssignment::STATUS_RETRY_SCHEDULED,
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_OUT_FOR_DELIVERY,
            ], true)) {
                throw new Exception("Cannot return package from status '{$assignment->status}'.");
            }

            /** @var Order|null $order */
            $order = $this->orderRepository->find($assignment->order_id);

            if (! $order) {
                throw new Exception("Order #{$assignment->order_id} not found.");
            }

            $hayestSource = $this->inventoryMovementService->getSourceByCode('hayest_central');

            if (! $hayestSource) {
                throw new Exception("Central warehouse source 'hayest_central' not found.");
            }

            // 2. Restore physical stock back to hayest_central once
            foreach ($order->items as $item) {
                $qtyReturned = $item->qty_shipped > 0 ? $item->qty_shipped : $item->qty_ordered;

                if ($qtyReturned <= 0) {
                    continue;
                }

                // Increment product_inventories for hayest_central
                $inventory = DB::table('product_inventories')
                    ->where('product_id', $item->product_id)
                    ->where('inventory_source_id', $hayestSource->id)
                    ->first();

                if ($inventory) {
                    DB::table('product_inventories')
                        ->where('id', $inventory->id)
                        ->increment('qty', $qtyReturned);
                } else {
                    DB::table('product_inventories')->insert([
                        'product_id' => $item->product_id,
                        'inventory_source_id' => $hayestSource->id,
                        'qty' => $qtyReturned,
                    ]);
                }

                // Record inventory movement for return
                $this->inventoryMovementService->recordMovement([
                    'movement_type' => 'return_from_delivery',
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'quantity' => $qtyReturned,
                    'target_inventory_source_id' => $hayestSource->id,
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'delivery_assignment_id' => $assignment->id,
                    'actor_id' => $supervisorId,
                    'actor_type' => 'supervisor',
                    'idempotency_key' => 'RETURN-'.$order->id.'-'.$item->id.'-'.$assignment->id,
                    'notes' => 'Return approved by supervisor. Reason: '.$reason,
                ]);
            }

            // 3. Update assignment status
            $assignment->update([
                'status' => DeliveryAssignment::STATUS_RETURNED_TO_HAYEST,
                'returned_at' => now(),
                'notes' => ($assignment->notes ? $assignment->notes."\n" : '')."Returned to Hayest Central by Supervisor #{$supervisorId}. Reason: {$reason}",
            ]);

            // 4. Update order status to canceled
            $this->orderRepository->updateOrderStatus($order, OrderModel::STATUS_CANCELED);

            Log::channel('delivery')->info("Order #{$order->id} returned to hayest_central and stock restored. Supervisor #{$supervisorId}");

            return $assignment->fresh();
        });
    }
}
