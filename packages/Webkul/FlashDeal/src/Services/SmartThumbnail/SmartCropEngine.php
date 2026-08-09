<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class SmartCropEngine
{
    protected float $targetRatio = 1.11258278; // 336 / 302

    protected int $targetWidth = 336;

    protected int $targetHeight = 302;

    protected float $minSubjectPreservation = 0.95; // 95% minimum subject preservation

    /**
     * Ratio difference threshold to decide between strategies.
     * If the original aspect ratio is within this % of target, use Smart Crop (Strategy A).
     */
    protected float $ratioTolerance = 0.15;

    /**
     * If subject covers more than this % of image area, use Contain+Pad (Strategy B).
     */
    protected float $subjectFillThreshold = 0.85;

    public function __construct(
        protected ImageAnalyzer $analyzer,
        protected WhitespaceDetector $detector,
        protected BackgroundExtractor $bgExtractor,
        protected ImageEnhancer $enhancer
    ) {}

    /**
     * Process original image using the best strategy for its characteristics.
     */
    public function process(ImageInterface $image, string $filePath, ?int $targetW = null, ?int $targetH = null): ImageInterface
    {
        $targetW = $targetW ?: $this->targetWidth;
        $targetH = $targetH ?: $this->targetHeight;
        $targetRatio = ($targetH > 0) ? ($targetW / $targetH) : $this->targetRatio;

        $analysis = $this->analyzer->analyze($image, $filePath);
        $boundingRegion = $this->detector->detectBoundingRegion($image);

        $origW = $analysis['width'];
        $origH = $analysis['height'];
        $origRatio = ($origH > 0) ? ($origW / $origH) : 1.0;

        // Calculate subject fill percentage
        $subjectArea = $boundingRegion['width'] * $boundingRegion['height'];
        $imageArea = max(1, $origW * $origH);
        $subjectFillPct = $subjectArea / $imageArea;

        // Calculate ratio difference
        $ratioDiff = abs($origRatio - $targetRatio) / $targetRatio;

        // Choose strategy
        $strategy = $this->chooseStrategy($ratioDiff, $subjectFillPct, $origRatio, $targetRatio);

        $result = match ($strategy) {
            'A' => $this->strategySmartCrop($image, $boundingRegion, $origW, $origH, $targetW, $targetH, $targetRatio),
            'B' => $this->strategyContainPad($image, $origW, $origH, $targetW, $targetH, $targetRatio),
            'C' => $this->strategyFocusCrop($image, $boundingRegion, $origW, $origH, $targetW, $targetH, $targetRatio),
            default => $this->strategyContainPad($image, $origW, $origH, $targetW, $targetH, $targetRatio),
        };

        // Apply quality enhancements
        $this->enhancer->enhance($result);

        return $result;
    }

    /**
     * Choose the best strategy based on image characteristics.
     */
    protected function chooseStrategy(float $ratioDiff, float $subjectFillPct, float $origRatio, float $targetRatio): string
    {
        // Strategy A: Original ratio is close to target — simple smart crop works well
        if ($ratioDiff <= $this->ratioTolerance) {
            return 'A';
        }

        // Strategy B: Subject fills most of the image — can't crop without losing product
        // This handles square images (1:1) where product fills 85%+ of frame
        if ($subjectFillPct >= $this->subjectFillThreshold) {
            return 'B';
        }

        // Strategy C: Subject is small within the image — aggressive focus crop is beneficial
        return 'C';
    }

    /**
     * Strategy A: Smart Crop — for images already close to target aspect ratio.
     *
     * Slight crop to match exact target ratio, centered on focal point.
     */
    protected function strategySmartCrop(
        ImageInterface $image,
        array $boundingRegion,
        int $origW,
        int $origH,
        int $targetW,
        int $targetH,
        float $targetRatio
    ): ImageInterface {
        // Focal Point: center-X, 40%-from-top-Y of subject
        $xf = $boundingRegion['x_min'] + (0.5 * $boundingRegion['width']);
        $yf = $boundingRegion['y_min'] + (0.4 * $boundingRegion['height']);

        // Candidate crop window matching target aspect ratio
        $cropW = $origW;
        $cropH = (int) round($cropW / $targetRatio);

        if ($cropH > $origH) {
            $cropH = $origH;
            $cropW = (int) round($cropH * $targetRatio);
        }

        // Ensure crop covers subject bounding region
        if ($cropW < $boundingRegion['width']) {
            $cropW = min($origW, (int) round($boundingRegion['width'] * 1.15));
            $cropH = (int) round($cropW / $targetRatio);
        }

        if ($cropH < $boundingRegion['height']) {
            $cropH = min($origH, (int) round($boundingRegion['height'] * 1.15));
            $cropW = (int) round($cropH * $targetRatio);
        }

        $cropW = min($origW, max(10, $cropW));
        $cropH = min($origH, max(10, $cropH));

        // Center crop around focal point
        $cropX = (int) round($xf - ($cropW / 2));
        $cropY = (int) round($yf - ($cropH / 2));

        $cropX = max(0, min($origW - $cropW, $cropX));
        $cropY = max(0, min($origH - $cropH, $cropY));

        $croppedImage = clone $image;
        $croppedImage->crop($cropW, $cropH, $cropX, $cropY);
        $croppedImage->resize($targetW, $targetH);

        return $croppedImage;
    }

    /**
     * Strategy B: Contain + Pad — for images where subject fills most of the frame.
     *
     * Preserves original aspect ratio by fitting image inside the target canvas,
     * then fills remaining space with the extracted background color.
     * This prevents distortion of product images (e.g., 1:1 square → 5:4 target).
     */
    protected function strategyContainPad(
        ImageInterface $image,
        int $origW,
        int $origH,
        int $targetW,
        int $targetH,
        float $targetRatio
    ): ImageInterface {
        $origRatio = ($origH > 0) ? ($origW / $origH) : 1.0;

        // Extract background color for padding
        $bgColor = $this->bgExtractor->extract($image);
        $bgHex = sprintf('#%02x%02x%02x', $bgColor['red'], $bgColor['green'], $bgColor['blue']);

        // Calculate contained dimensions (fit inside target while preserving ratio)
        if ($origRatio > $targetRatio) {
            // Image is wider than target — fit by width, pad top/bottom
            $containedW = $targetW;
            $containedH = (int) round($targetW / $origRatio);
        } else {
            // Image is taller than target — fit by height, pad left/right
            $containedH = $targetH;
            $containedW = (int) round($targetH * $origRatio);
        }

        // Ensure contained dimensions don't exceed target
        $containedW = min($targetW, max(1, $containedW));
        $containedH = min($targetH, max(1, $containedH));

        // Resize the original image to contained dimensions
        $resized = clone $image;
        $resized->resize($containedW, $containedH);

        // Create canvas with background color
        $canvas = image_manager()->create($targetW, $targetH)->fill($bgHex);

        // Center the resized image on the canvas
        $offsetX = (int) round(($targetW - $containedW) / 2);
        $offsetY = (int) round(($targetH - $containedH) / 2);

        $canvas->place($resized, 'top-left', $offsetX, $offsetY);

        return $canvas;
    }

    /**
     * Strategy C: Focus Crop — for images with large empty/whitespace areas.
     *
     * Aggressively crops around the detected subject with a small margin,
     * then resizes to the target dimensions. This removes wasted whitespace.
     */
    protected function strategyFocusCrop(
        ImageInterface $image,
        array $boundingRegion,
        int $origW,
        int $origH,
        int $targetW,
        int $targetH,
        float $targetRatio
    ): ImageInterface {
        // Add 10% margin around subject
        $marginFactor = 0.10;
        $marginX = (int) round($boundingRegion['width'] * $marginFactor);
        $marginY = (int) round($boundingRegion['height'] * $marginFactor);

        $subjectX = max(0, $boundingRegion['x_min'] - $marginX);
        $subjectY = max(0, $boundingRegion['y_min'] - $marginY);
        $subjectW = min($origW - $subjectX, $boundingRegion['width'] + (2 * $marginX));
        $subjectH = min($origH - $subjectY, $boundingRegion['height'] + (2 * $marginY));

        // Adjust to match target aspect ratio
        $subjectRatio = ($subjectH > 0) ? ($subjectW / $subjectH) : $targetRatio;

        if ($subjectRatio > $targetRatio) {
            // Subject area is wider — expand height
            $newH = (int) round($subjectW / $targetRatio);
            $expandY = (int) round(($newH - $subjectH) / 2);
            $subjectY = max(0, $subjectY - $expandY);
            $subjectH = min($origH - $subjectY, $newH);
            // Re-adjust width if height was clamped
            $subjectW = (int) round($subjectH * $targetRatio);
            $subjectW = min($origW - $subjectX, $subjectW);
        } else {
            // Subject area is taller — expand width
            $newW = (int) round($subjectH * $targetRatio);
            $expandX = (int) round(($newW - $subjectW) / 2);
            $subjectX = max(0, $subjectX - $expandX);
            $subjectW = min($origW - $subjectX, $newW);
            // Re-adjust height if width was clamped
            $subjectH = (int) round($subjectW / $targetRatio);
            $subjectH = min($origH - $subjectY, $subjectH);
        }

        $subjectW = max(10, $subjectW);
        $subjectH = max(10, $subjectH);

        // If the focused crop is still very close to the full image,
        // fall back to Contain+Pad to avoid pointless near-full crop
        $focusCoverage = ($subjectW * $subjectH) / max(1, $origW * $origH);
        if ($focusCoverage > 0.90) {
            return $this->strategyContainPad($image, $origW, $origH, $targetW, $targetH, $targetRatio);
        }

        $croppedImage = clone $image;
        $croppedImage->crop($subjectW, $subjectH, $subjectX, $subjectY);
        $croppedImage->resize($targetW, $targetH);

        return $croppedImage;
    }
}
