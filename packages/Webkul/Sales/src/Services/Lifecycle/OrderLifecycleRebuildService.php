<?php

namespace Webkul\Sales\Services\Lifecycle;

use Webkul\Sales\Models\Order;

class OrderLifecycleRebuildService
{
    public function __construct(
        protected OrderLifecycleProjector $projector
    ) {}

    /**
     * Rebuild Read Model for all orders or array of order IDs.
     *
     * @return int Count of processed orders
     */
    public function rebuild(?array $orderIds = null): int
    {
        $query = Order::query();

        if ($orderIds !== null) {
            $query->whereIn('id', $orderIds);
        }

        $count = 0;
        $query->chunk(100, function ($orders) use (&$count) {
            foreach ($orders as $order) {
                $this->projector->project($order);
                $count++;
            }
        });

        return $count;
    }
}
