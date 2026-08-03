<?php

namespace App\Enums;

enum SourceDiscountPolicy: string
{
    /**
     * Source discount is passed through to customer as a promotional special price.
     * Generates regular retail price from original list cost and special price from actual acquisition cost.
     */
    case PASS_TO_CUSTOMER = 'PASS_TO_CUSTOMER';

    /**
     * Source discount remains internal to HIGEST.
     * Customer sees standard HIGEST calculated price based on actual acquisition cost (special_price is null).
     */
    case ABSORB_BY_HIGEST = 'ABSORB_BY_HIGEST';

    public function label(): string
    {
        return match ($this) {
            self::PASS_TO_CUSTOMER => 'Pass source discount to customer (Show sale badge)',
            self::ABSORB_BY_HIGEST => 'Absorb source discount (Show clean flat price)',
        };
    }
}
