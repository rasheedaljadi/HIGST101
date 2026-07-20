<?php

namespace Webkul\Fulfillment\Contracts;

use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;

interface ExternalFulfillmentProviderInterface
{
    public function code(): string;

    public function isConfigured(int $providerAccountId): bool;

    public function createSupplierOrder(
        SupplierOrderRequest $request,
        string $contractVersion
    ): SupplierOrderResult;

    public function getSupplierOrderStatus(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion
    ): SupplierOrderStatus;

    public function cancelSupplierOrder(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion
    ): SupplierOrderResult;

    public function findByReference(
        string $internalReference,
        int $providerAccountId,
        string $contractVersion
    ): ?string;
}
