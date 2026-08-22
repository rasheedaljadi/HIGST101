<?php

namespace Webkul\Procurement\DTO;

class ExternalOrderDraft
{
    /**
     * @param  array<int, array{supplier_product_id: string|int, supplier_sku_id: string|int, qty: int, expected_unit_cost: float, sku_attr?: string|null, logistics_service_name?: string|null}>  $items
     * @param  array<string, mixed>|null  $overrideShippingAddress
     */
    public function __construct(
        public readonly int|string $supplierPurchaseOrderId,
        public readonly string $correlationKey,
        public readonly array $items,
        public readonly string $currencyCode = 'USD',
        public readonly ?int $providerAccountId = null,
        public readonly ?array $overrideShippingAddress = null
    ) {}
}
