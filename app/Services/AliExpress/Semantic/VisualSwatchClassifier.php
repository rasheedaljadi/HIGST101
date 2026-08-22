<?php

namespace App\Services\AliExpress\Semantic;

use App\Services\AliExpress\DTO\NormalizedVariant;
use App\Services\AliExpress\DTO\NormalizedVariantAxis;
use App\Services\AliExpress\Semantic\DTO\ClassifiedAxis;

/**
 * Layer 2: Visual Swatch Classifier.
 *
 * Detects axes where each option is associated with a distinct product image
 * (sku_image). When most values have unique images, the axis represents a
 * visual distinction — typically color, model, or design pattern — regardless
 * of whatever name the seller assigned to the axis.
 *
 * This layer catches visual axes that the pattern classifier missed (e.g. an
 * axis named "款式" or "Type" whose values are model names, each with its
 * own thumbnail).
 */
class VisualSwatchClassifier
{
    /** Minimum ratio of values with distinct images to classify as visual. */
    protected const IMAGE_COVERAGE_THRESHOLD = 0.60;

    /** Minimum number of distinct images required. */
    protected const MIN_DISTINCT_IMAGES = 2;

    /**
     * Classify an axis as a visual variant based on image associations.
     *
     * @param  NormalizedVariantAxis  $axis  The axis to classify.
     * @param  NormalizedVariant[]  $variants  All product variants (to extract per-axis images).
     * @return ClassifiedAxis|null Classification result, or null if not visual.
     */
    public function classify(NormalizedVariantAxis $axis, array $variants): ?ClassifiedAxis
    {
        if ($axis->values === [] || $variants === []) {
            return null;
        }

        $imagesPerValue = $this->extractImagesPerValue($axis, $variants);

        if ($imagesPerValue === []) {
            return null;
        }

        $distinctImages = array_unique(array_values($imagesPerValue));
        $totalValues = count($axis->values);
        $coverage = count($imagesPerValue) / $totalValues;

        // Need at least 2 distinct images and 60% value coverage
        if (count($distinctImages) >= self::MIN_DISTINCT_IMAGES && $coverage >= self::IMAGE_COVERAGE_THRESHOLD) {
            return new ClassifiedAxis(
                originalAxis: $axis,
                semanticType: 'visual_variant',
                arabicLabel: 'اللون / الموديل',
                englishLabel: 'Color / Model',
                confidence: round($coverage, 2),
                classifiedBy: 'visual',
            );
        }

        return null;
    }

    /**
     * Extract the mapping of option values to their associated images.
     *
     * Iterates all variants and for each axis value, finds if a distinct
     * sku_image was provided. The mapper stores variant images in the
     * NormalizedVariant::$images array, and options in ::$options keyed by
     * axis display name.
     *
     * @return array<string, string> value => imageUrl
     */
    protected function extractImagesPerValue(NormalizedVariantAxis $axis, array $variants): array
    {
        $imagesPerValue = [];

        foreach ($variants as $variant) {
            if (! $variant instanceof NormalizedVariant) {
                continue;
            }

            // Find this axis's value in the variant's options
            $axisValue = $variant->options[$axis->name] ?? null;

            if ($axisValue === null) {
                continue;
            }

            // The variant's images array contains sku_images collected during parsing
            $variantImages = $variant->images ?? [];

            if ($variantImages !== [] && ! isset($imagesPerValue[$axisValue])) {
                $imagesPerValue[$axisValue] = $variantImages[0];
            }
        }

        return $imagesPerValue;
    }
}
