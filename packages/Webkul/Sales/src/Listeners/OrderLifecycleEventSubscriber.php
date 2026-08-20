<?php

namespace Webkul\Sales\Listeners;

use Illuminate\Events\Dispatcher;
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
        } elseif (is_object($order) && isset($order->id)) {
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
            } elseif (isset($payload->id)) {
                $this->projector->project($payload);
            }
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
