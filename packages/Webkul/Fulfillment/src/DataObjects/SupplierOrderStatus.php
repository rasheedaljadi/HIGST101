<?php

namespace Webkul\Fulfillment\DataObjects;

/**
 * Normalized status of a supplier order returned by
 * `FulfillmentProviderInterface::getSupplierOrderStatus()`.
 *
 * The adapter maps the raw upstream state onto exactly one value of the
 * State_Dictionary (design section 5.3); unknown/empty raw states map to
 * `needs_manual_review`. Tracking fields are populated when the provider
 * exposes them (design section 5.8 and Requirement 8).
 *
 * Plain, framework-agnostic, immutable value object.
 */
final class SupplierOrderStatus
{
    /**
     * @param  string  $mappedState  A value from the State_Dictionary (design section 5.3).
     * @param  string|null  $rawState  The raw supplier state string, for auditing.
     * @param  string|null  $trackingNumber  Shipment tracking number, when available.
     * @param  string|null  $trackingCompany  Shipment carrier/company, when available.
     */
    public function __construct(
        public readonly string $mappedState,
        public readonly ?string $rawState = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $trackingCompany = null,
    ) {}
}
