<?php

namespace Webkul\Fulfillment\Services\Application;

use Webkul\Fulfillment\Commands\ReserveAllocationCommand;
use Webkul\Fulfillment\Handlers\ReserveAllocationHandler;
use Webkul\Fulfillment\Models\OrderAllocation;

class LocalFulfillmentWorkflow
{
    /**
     * Create a new workflow instance.
     */
    public function __construct(protected ReserveAllocationHandler $reserveHandler) {}

    /**
     * Process a local warehouse allocation.
     *
     * @param  int  $orderId
     * @param  int  $orderItemId
     * @param  int  $qty
     * @param  string  $correlationId
     * @param  string  $causationId
     * @return \Webkul\Fulfillment\Models\OrderAllocation
     */
    public function processLocal(int $orderId, int $orderItemId, int $qty, string $correlationId, string $causationId): OrderAllocation
    {
        $command = new ReserveAllocationCommand(
            orderId: $orderId,
            orderItemId: $orderItemId,
            allocationType: 'warehouse',
            sourceCode: 'warehouse_riyadh',
            quantity: $qty,
            correlationId: $correlationId,
            causationId: $causationId
        );

        return $this->reserveHandler->handle($command);
    }
}
