<?php

namespace Webkul\Fulfillment\Enums;

enum TransferStatus: string
{
    case DRAFT = 'draft';
    case IN_TRANSIT = 'in_transit';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
    case DISCREPANCY = 'discrepancy';

    /**
     * Get human readable label for the transfer status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Manifest',
            self::IN_TRANSIT => 'In Transit (Dispatched)',
            self::PARTIALLY_RECEIVED => 'Partially Received in Yemen',
            self::RECEIVED => 'Fully Received & Stocked',
            self::CANCELLED => 'Cancelled',
            self::DISCREPANCY => 'Discrepancy / QA Exception',
        };
    }

    /**
     * Check if manifest is in a final closed state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::RECEIVED, self::CANCELLED], true);
    }
}
