<?php

namespace App\Services\AliExpress\Semantic\DTO;

use App\Services\AliExpress\DTO\NormalizedVariantAxis;

/**
 * A variant axis that has been semantically classified by the SAE.
 *
 * Contains the original axis data plus the semantic analysis results:
 * what the axis actually represents (storage, color, shoe size, etc.),
 * the proper Arabic/English labels, and the classification confidence.
 */
final class ClassifiedAxis
{
    public function __construct(
        /** The original unmodified axis from the mapper. */
        public readonly NormalizedVariantAxis $originalAxis,

        /**
         * The semantic type discovered by the classifier pipeline.
         * Examples: storage_capacity, color, shoe_size, apparel_size,
         * battery_capacity, plug_type, origin_country, feature_toggle,
         * edition, bundle_variant, region_version, liquid_volume,
         * visual_variant, flavor, finish_type, position_variant,
         * power_wattage, voltage, screen_size, dimension,
         * ram_storage_combo, generic_variant, feature_descriptor.
         */
        public readonly string $semanticType,

        /** The proper Arabic label (e.g. "سعة التخزين" instead of "المقاس"). */
        public readonly string $arabicLabel,

        /** The proper English label (e.g. "Storage Capacity" instead of "Size"). */
        public readonly string $englishLabel,

        /** Classification confidence: 0.00 — 1.00. */
        public readonly float $confidence,

        /**
         * Which classifier produced this result.
         * One of: pattern, visual, contextual, memory, admin_override.
         */
        public readonly string $classifiedBy,
    ) {}
}
