<?php

namespace App\Enums;

/**
 * Typed pricing operation triggers for audit logging and domain events.
 */
enum PricingTrigger: string
{
    case IMPORT = 'import';
    case SYNC = 'sync';
    case RULE_CHANGE = 'rule_change';
    case MANUAL = 'manual';
    case MIGRATION = 'migration';
}
