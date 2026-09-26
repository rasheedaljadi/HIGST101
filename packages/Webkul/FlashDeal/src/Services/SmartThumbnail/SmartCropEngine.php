<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;
use Webkul\Product\Contracts\Product;

class SmartCropEngine
{
    protected float $targetRatio = 1.0;

    protected int $targetWidth = 400;

    protected int $targetHeight = 400;

    public function __construct(
        protected ImageAnalyzer $analyzer,
        protected WhitespaceDetector $detector,
        protected BackgroundExtractor $bgExtractor,
        protected ImageEnhancer $enhancer,
        protected ProductSemanticAnalyzer $semanticAnalyzer
    ) {}

    /**
     * Process original image: Pure Enhancement & WebP Optimization mode.
     * ZERO cropping, ZERO color filling/padding.
     * Preserves 100% of the original image with light contrast/clarity enhancement.
     */
    public function process(
        ImageInterface $image,
        string $filePath,
        ?int $targetW = null,
        ?int $targetH = null,
        ?Product $product = null
    ): ImageInterface {
        $targetW = $targetW ?: $this->targetWidth;
        $targetH = $targetH ?: $this->targetHeight;

        $analysis = $this->analyzer->analyze($image, $filePath);
        $origW = $analysis['width'];
        $origH = $analysis['height'];

        $processed = clone $image;

        // Scale down cleanly to target dimensions if larger, preserving 100% of original aspect ratio.
        // ZERO cropping, ZERO color padding or background injection.
        if ($origW > $targetW || $origH > $targetH) {
            $processed->scaleDown($targetW, $targetH);
        }

        // Apply light sharpening, subtle contrast, and brightness lift
        $this->enhancer->enhance($processed);

        return $processed;
    }
}
