<?php

namespace App\Services\AliExpress\Semantic;

use App\Services\AliExpress\Semantic\DTO\AxisClassificationResult;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;

/**
 * Axis Decision Engine.
 *
 * Takes an array of semantically classified axes and separates them into
 * three groups:
 *  - purchaseAxes: shown as interactive purchase buttons (super_attributes)
 *  - specAxes: displayed as static technical specifications
 *  - droppedAxes: removed entirely (e.g. single-option Ships From)
 *
 * Decision rules are designed to be conservative: when in doubt, an axis
 * remains as a purchase button. Only clearly single-valued or explicitly
 * non-interactive semantic types get demoted or dropped.
 */
class AxisDecisionEngine
{
    /**
     * Semantic types that should ALWAYS be dropped when they have ≤1 option.
     * These types are purely logistical and never interesting to the customer.
     */
    protected const ALWAYS_DROP_WHEN_SINGLE = [
        'origin_country',
        'plug_type',
    ];

    /**
     * Semantic types that should be demoted to spec when they have ≤1 option.
     * These carry useful information but are not a purchase choice.
     */
    protected const DEMOTE_TO_SPEC_WHEN_SINGLE = [
        'battery_capacity',
        'power_wattage',
        'voltage',
        'screen_size',
        'dimension',
        'feature_descriptor',
    ];

    /**
     * Semantic types that are valid purchase axes when they have >1 option.
     * This is the inclusive list — any type not in ALWAYS_DROP is eligible.
     */
    protected const PURCHASE_AXIS_TYPES = [
        'color',
        'visual_variant',
        'storage_capacity',
        'ram_storage_combo',
        'apparel_size',
        'shoe_size',
        'edition',
        'bundle_variant',
        'feature_toggle',
        'liquid_volume',
        'flavor',
        'finish_type',
        'region_version',
        'plug_type',
        'origin_country',
        'position_variant',
        'quantity_pack',
        'generic_variant',
        'battery_capacity',
        'power_wattage',
        'voltage',
        'screen_size',
        'dimension',
    ];

    /**
     * Apply decision rules to classified axes and produce the final result.
     *
     * @param  ClassifiedAxis[]  $classifiedAxes
     */
    public function decide(array $classifiedAxes): AxisClassificationResult
    {
        $purchaseAxes = [];
        $specAxes = [];
        $droppedAxes = [];

        // First pass: identify which axes have multiple options
        $multiOptionAxes = array_filter(
            $classifiedAxes,
            fn (ClassifiedAxis $ca) => count($ca->originalAxis->values) > 1,
        );

        $hasMultiOptionAxes = $multiOptionAxes !== [];

        foreach ($classifiedAxes as $classified) {
            $valueCount = count($classified->originalAxis->values);
            $type = $classified->semanticType;

            // ── Rule 1: Single-option axes ──────────────────────────────

            if ($valueCount <= 1) {
                // Rule 1a: Always drop origin_country with single value
                if (in_array($type, self::ALWAYS_DROP_WHEN_SINGLE, true)) {
                    $droppedAxes[] = $classified;

                    continue;
                }

                // Rule 1b: Demote to spec if it carries useful info
                if (in_array($type, self::DEMOTE_TO_SPEC_WHEN_SINGLE, true)) {
                    $specAxes[] = $classified;

                    continue;
                }

                // Rule 1c: If there are other multi-option axes, drop this one
                if ($hasMultiOptionAxes) {
                    // Single-value generic or color with other real axes = drop
                    $droppedAxes[] = $classified;

                    continue;
                }

                // Rule 1d: If this is the ONLY axis (no multi-option ones),
                // keep it as a purchase axis (the product needs at least one)
                $purchaseAxes[] = $classified;

                continue;
            }

            // ── Rule 2: Multi-option axes (valueCount > 1) ──────────────

            // Rule 2a: Origin country with multiple warehouses = purchase axis
            // Rule 2b: Any recognized type with multiple options = purchase axis
            $purchaseAxes[] = $classified;
        }

        return new AxisClassificationResult(
            purchaseAxes: $purchaseAxes,
            specAxes: $specAxes,
            droppedAxes: $droppedAxes,
        );
    }
}
