<?php

namespace Webkul\Fulfillment\DataObjects;

class PurchaseOrderAction
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public string $action, // MARK_SUBMITTED, MARK_SHIPPED, MARK_NEEDS_REVIEW, MARK_CANCELED, etc.
        public array $attributes = []
    ) {}

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'action'     => $this->action,
            'attributes' => $this->attributes,
        ];
    }
}
