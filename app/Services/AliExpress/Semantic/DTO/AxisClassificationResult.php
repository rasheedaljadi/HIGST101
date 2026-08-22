<?php

namespace App\Services\AliExpress\Semantic\DTO;

use App\Services\AliExpress\DTO\NormalizedVariantAxis;

/**
 * The final output of the Semantic Attribute Engine after classification
 * and decision-making are complete.
 *
 * Separates axes into three groups:
 *  - purchaseAxes: shown as interactive selection buttons (super_attributes)
 *  - specAxes: displayed as static technical specifications
 *  - droppedAxes: removed entirely (e.g. single-option Ships From)
 */
final class AxisClassificationResult
{
    /**
     * @param  ClassifiedAxis[]  $purchaseAxes  Axes shown as purchase buttons.
     * @param  ClassifiedAxis[]  $specAxes  Axes shown as static specs.
     * @param  ClassifiedAxis[]  $droppedAxes  Axes removed entirely.
     */
    public function __construct(
        public readonly array $purchaseAxes,
        public readonly array $specAxes,
        public readonly array $droppedAxes,
    ) {}

    /**
     * Get the original NormalizedVariantAxis objects for purchase axes only.
     *
     * @return NormalizedVariantAxis[]
     */
    public function getPurchaseVariantAxes(): array
    {
        return array_map(
            fn (ClassifiedAxis $ca) => $ca->originalAxis,
            $this->purchaseAxes,
        );
    }

    /**
     * Whether any axes were classified as specifications.
     */
    public function hasSpecAxes(): bool
    {
        return $this->specAxes !== [];
    }

    /**
     * Whether any axes were dropped.
     */
    public function hasDroppedAxes(): bool
    {
        return $this->droppedAxes !== [];
    }
}
