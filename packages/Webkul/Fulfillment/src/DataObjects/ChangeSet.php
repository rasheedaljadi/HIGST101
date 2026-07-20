<?php

namespace Webkul\Fulfillment\DataObjects;

class ChangeSet
{
    public function __construct(
        public readonly int $productId,
        public readonly string $supplierProductId,
        private array $changes = []
    ) {}

    public function addChange(string $type, ?int $variantId, array $payload): void
    {
        $this->changes[] = [
            'type'       => $type,
            'variant_id' => $variantId,
            'payload'    => $payload,
        ];
    }

    public function hasChanges(): bool
    {
        return !empty($this->changes);
    }

    public function getChanges(): array
    {
        return $this->changes;
    }
}
