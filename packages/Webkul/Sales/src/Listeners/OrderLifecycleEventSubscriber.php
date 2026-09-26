<?php

namespace Webkul\Sales\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Fulfillment\Models\InboundReceiptManifest;
use Webkul\Fulfillment\Models\InventoryTransferManifest;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleProjector;

class OrderLifecycleEventSubscriber
{
    public function __construct(
        protected OrderLifecycleProjector $projector
    ) {}

    /**
     * Handle direct order events.
     */
    public function handleOrderChange(mixed $order): void
    {
        if (is_numeric($order)) {
            $this->projector->project((int) $order);
        } elseif ($order instanceof Order) {
            $this->projector->project($order);
        }
    }

    /**
     * Handle payload object or entity events (Invoices, POs, Receipts, Transfers, Deliveries).
     */
    public function handleGenericChange(mixed $payload): void
    {
        if (is_numeric($payload)) {
            $this->projector->project((int) $payload);
        } elseif (is_object($payload)) {
            if (isset($payload->order_id) && ! empty($payload->order_id)) {
                $this->projector->project((int) $payload->order_id);
            } elseif ($payload instanceof Order) {
                $this->projector->project($payload);
            } elseif ($payload instanceof InventoryTransferManifest || $payload instanceof InboundReceiptManifest) {
                $this->handleManifestChange($payload);
            }
        }
    }

    /**
     * Project orders related to inventory transfer or receipt manifest.
     */
    protected function handleManifestChange(mixed $manifest): void
    {
        try {
            $items = $manifest->items ?? collect();
            if ($items->isEmpty() && method_exists($manifest, 'items')) {
                $items = $manifest->items()->get();
            }

            if ($items->isEmpty()) {
                return;
            }

            $orderItemIds = $items->pluck('order_item_id')->filter()->unique()->toArray();
            $skus = $items->pluck('sku')->filter()->unique()->toArray();

            if (empty($orderItemIds) && empty($skus)) {
                return;
            }

            $query = DB::table('order_items');
            $query->where(function ($q) use ($orderItemIds, $skus) {
                if (! empty($orderItemIds)) {
                    $q->whereIn('id', $orderItemIds);
                }
                if (! empty($skus)) {
                    $q->orWhereIn('sku', $skus);
                }
            });

            $orderIds = $query->distinct()->pluck('order_id');

            foreach ($orderIds as $orderId) {
                $this->projector->project((int) $orderId);
            }
        } catch (Throwable $e) {
            Log::warning("OrderLifecycleEventSubscriber: Failed to project manifest change: {$e->getMessage()}");
        }
    }

    /**
     * Register domain event listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        // Sales & Order Events
        $events->listen('sales.order.place.after', [self::class, 'handleOrderChange']);
        $events->listen('sales.order.save.after', [self::class, 'handleOrderChange']);
        $events->listen('sales.order.create.after', [self::class, 'handleOrderChange']);
        $events->listen('sales.order.update-status.after', [self::class, 'handleOrderChange']);
        $events->listen('sales.order.cancel.after', [self::class, 'handleOrderChange']);
        $events->listen('sales.invoice.save.after', [self::class, 'handleGenericChange']);

        // Procurement & Fulfillment Events
        $events->listen('fulfillment.purchase_order.create.after', [self::class, 'handleGenericChange']);
        $events->listen('fulfillment.purchase_order.update.after', [self::class, 'handleGenericChange']);

        // Inventory & Logistics Events
        $events->listen('inventory.inbound_receipt.completed', [self::class, 'handleGenericChange']);
        $events->listen('inventory.transfer_manifest.in_transit', [self::class, 'handleGenericChange']);
        $events->listen('inventory.transfer_manifest.completed', [self::class, 'handleGenericChange']);

        // Delivery Management Events
        $events->listen('delivery.assignment.created', [self::class, 'handleGenericChange']);
        $events->listen('delivery.assignment.updated', [self::class, 'handleGenericChange']);
    }
}
