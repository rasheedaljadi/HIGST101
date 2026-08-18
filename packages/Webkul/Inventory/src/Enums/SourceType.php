<?php

namespace Webkul\Inventory\Enums;

enum SourceType: string
{
    case VIRTUAL_PROJECTION = 'virtual_projection';
    case SOURCING_STAGING = 'sourcing_staging';
    case QUARANTINE = 'quarantine';
    case DROPSHIP_DISTRIBUTION = 'dropship_distribution';
    case INTERNAL_STOCK = 'internal_stock';
    case GENERAL = 'general';

    /**
     * Get human readable label for the source type.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIRTUAL_PROJECTION => 'Virtual Catalog Projection',
            self::SOURCING_STAGING => 'Cross-Border Sourcing & Staging',
            self::QUARANTINE => 'Quality Inspection & Quarantine',
            self::DROPSHIP_DISTRIBUTION => 'Yemen Dropship Distribution Hub',
            self::INTERNAL_STOCK => 'Domestic Ready Stock Warehouse',
            self::GENERAL => 'General Warehouse',
        };
    }

    /**
     * Determine if this source type can have storefront salable stock.
     */
    public function isSalable(): bool
    {
        return match ($this) {
            self::INTERNAL_STOCK, self::DROPSHIP_DISTRIBUTION => true,
            default => false,
        };
    }

    /**
     * Determine if local last-mile courier delivery can originate directly from this source.
     */
    public function isDeliveryCapable(): bool
    {
        return match ($this) {
            self::INTERNAL_STOCK, self::DROPSHIP_DISTRIBUTION => true,
            default => false,
        };
    }

    /**
     * Determine if this is a quarantine source.
     */
    public function isQuarantine(): bool
    {
        return $this === self::QUARANTINE;
    }

    /**
     * Determine if this is a virtual catalog projection source.
     */
    public function isVirtual(): bool
    {
        return $this === self::VIRTUAL_PROJECTION;
    }
}
