<?php

namespace Webkul\Fulfillment\DataObjects;

/**
 * A single line of a provider-agnostic supplier order request.
 *
 * Built by the FulfillmentService from a Bagisto `order_item` joined with its
 * linked `AliExpressProductImport` record (see design section 5.8 Data Flow).
 * The FulfillmentService carries the supplier product identifiers so the
 * adapter does not need to re-query the provider API to resolve them.
 *
 * Plain, framework-agnostic, immutable value object.
 */
final class SupplierOrderLine
{
    /**
     * @param  string  $aliexpressProductId  Supplier product identifier (from AliExpressProductImport).
     * @param  string|null  $skuId  Supplier SKU/variant identifier (nullable for single-variant products).
     * @param  int  $qty  Ordered quantity (must be a positive integer).
     */
    public function __construct(
        public readonly string $aliexpressProductId,
        public readonly ?string $skuId,
        public readonly int $qty,
    ) {}
}
