<?php

namespace Webkul\Fulfillment\Events;

class HayestStockReceived
{
    public function __construct(
        public int $orderId,
        public int $orderItemId,
        public int $productId,
        public int $quantity,
        public string $inventorySourceCode,
        public int $purchaseOrderId,
        public ?int $purchaseOrderItemId,
        public string $idempotencyKey,
        public ?string $correlationId = null
    ) {}
}
