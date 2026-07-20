<?php

namespace Webkul\Fulfillment\Services\Domain;

class ExternalIdentityMapper
{
    public function mapInternalToExternal(int $purchaseOrderId, int $allocationId): string
    {
        return "EXT-PO-{$purchaseOrderId}-AL-{$allocationId}";
    }

    public function extractInternalIds(string $externalOrderId): array
    {
        if (preg_match('/EXT-PO-(\d+)-AL-(\d+)/', $externalOrderId, $matches)) {
            return [
                'purchase_order_id' => (int) $matches[1],
                'allocation_id'     => (int) $matches[2],
            ];
        }

        return [
            'purchase_order_id' => null,
            'allocation_id'     => null,
        ];
    }
}
