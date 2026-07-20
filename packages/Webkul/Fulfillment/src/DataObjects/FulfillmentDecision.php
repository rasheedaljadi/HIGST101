<?php

namespace Webkul\Fulfillment\DataObjects;

class FulfillmentDecision
{
    public function __construct(
        public string $source,
        public ?string $provider,
        public ?string $warehouse,
        public string $reason,
        public int $confidence,
        public string $decision_version
    ) {}

    /**
     * Determine if routing is to a local warehouse.
     */
    public function isLocal(): bool
    {
        return $this->source === 'LOCAL';
    }

    /**
     * Determine if routing is to a dropshipping supplier.
     */
    public function isSupplier(): bool
    {
        return $this->source === 'SUPPLIER';
    }
}
