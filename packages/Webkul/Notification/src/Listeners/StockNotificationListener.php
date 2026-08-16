<?php

namespace Webkul\Notification\Listeners;

use Webkul\Notification\Services\StockNotificationService;

class StockNotificationListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected StockNotificationService $stockNotificationService
    ) {}

    /**
     * Handle stock check after an order is placed.
     */
    public function afterOrderCreated(mixed $order): void
    {
        $this->stockNotificationService->checkOrderProducts($order);
    }

    /**
     * Handle stock check after a product is created.
     */
    public function afterProductCreated(mixed $product): void
    {
        $this->stockNotificationService->checkProductStock($product);
    }

    /**
     * Handle stock check after a product is updated.
     */
    public function afterProductUpdated(mixed $product): void
    {
        $this->stockNotificationService->checkProductStock($product);
    }

    /**
     * Handle stock check after a refund is saved.
     */
    public function afterRefundCreated(mixed $refund): void
    {
        if ($refund && $refund->order) {
            $this->stockNotificationService->checkOrderProducts($refund->order);
        }
    }
}
