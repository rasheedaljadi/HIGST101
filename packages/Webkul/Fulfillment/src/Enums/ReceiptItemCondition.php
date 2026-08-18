<?php

namespace Webkul\Fulfillment\Enums;

enum ReceiptItemCondition: string
{
    case GOOD = 'good';
    case DAMAGED = 'damaged';
    case MISSING = 'missing';
    case WRONG_ITEM = 'wrong_item';

    /**
     * Get human readable label for item condition upon receipt.
     */
    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Good Condition (Accepted)',
            self::DAMAGED => 'Damaged / Defective (Quarantine)',
            self::MISSING => 'Missing / Shortage (Discrepancy)',
            self::WRONG_ITEM => 'Wrong Item Received (Quarantine)',
        };
    }

    /**
     * Determine if condition allows regular stock-in to salable inventory.
     */
    public function isAccepted(): bool
    {
        return $this === self::GOOD;
    }

    /**
     * Determine if item should be routed to quarantine inventory.
     */
    public function shouldQuarantine(): bool
    {
        return in_array($this, [self::DAMAGED, self::WRONG_ITEM], true);
    }
}
