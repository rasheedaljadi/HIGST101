<?php

namespace Webkul\Fulfillment\DataObjects;

/**
 * Provider-agnostic request to create a supplier order.
 *
 * Assembled by the FulfillmentService for one supplier group and passed to
 * `FulfillmentProviderInterface::createSupplierOrder()` (see ADR-002 and design
 * section 5.8 Data Flow). It carries only neutral data; the adapter is
 * responsible for translating it into the shape the upstream provider expects.
 *
 * Preconditions (enforced by the FulfillmentService, see design section 6.3):
 * `$idempotencyKey` and `$internalReference` are non-empty and `$items` is not empty.
 *
 * Plain, framework-agnostic, immutable value object.
 */
final class SupplierOrderRequest
{
    /**
     * @param  string  $internalReference  Our out order id (= purchase_orders.internal_reference), used for reconciliation.
     * @param  string  $idempotencyKey  Internal idempotency key for tracing (= purchase_orders.idempotency_key).
     * @param  ShippingAddress  $shippingAddress  Recipient shipping address, sourced from the Customer_Order.
     * @param  SupplierOrderLine[]  $items  Non-empty list of order lines.
     * @param  string  $currency  Currency code for the request context.
     */
    public function __construct(
        public readonly string $internalReference,
        public readonly string $idempotencyKey,
        public readonly ShippingAddress $shippingAddress,
        public readonly array $items,
        public readonly string $currency,
        public readonly ?int $providerAccountId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'internalReference' => $this->internalReference,
            'idempotencyKey'    => $this->idempotencyKey,
            'shippingAddress'   => $this->shippingAddress ? [
                'firstName'   => $this->shippingAddress->firstName,
                'lastName'    => $this->shippingAddress->lastName,
                'address'     => $this->shippingAddress->address,
                'city'        => $this->shippingAddress->city,
                'state'       => $this->shippingAddress->state,
                'postcode'    => $this->shippingAddress->postcode,
                'country'     => $this->shippingAddress->country,
                'phone'       => $this->shippingAddress->phone,
                'email'       => $this->shippingAddress->email,
                'companyName' => $this->shippingAddress->companyName,
            ] : null,
            'items'             => array_map(fn($item) => [
                'aliexpressProductId' => $item->aliexpressProductId,
                'skuId'               => $item->skuId,
                'qty'                 => $item->qty,
            ], $this->items),
            'currency'          => $this->currency,
            'providerAccountId' => $this->providerAccountId,
        ];
    }
}
