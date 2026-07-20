<?php

namespace Webkul\Fulfillment\Contracts;

use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;

interface FulfillmentProviderInterface
{
    /**
     * Unique identifier for the provider, e.g., "aliexpress".
     */
    public function code(): string;

    /**
     * Determine if the provider is configured and ready for calls.
     */
    public function isConfigured(): bool;

    /**
     * Submit an order request to the supplier.
     */
    public function createSupplierOrder(SupplierOrderRequest $request): SupplierOrderResult;

    /**
     * Get the supplier order status using the external ID.
     */
    public function getSupplierOrderStatus(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderStatus;

    /**
     * Attempt to find the supplier order using our internal reference.
     */
    public function findByReference(string $internalReference, ?int $providerAccountId = null): ?string;

    /**
     * Attempt to cancel the supplier order (best-effort).
     */
    public function cancelSupplierOrder(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderResult;
}
