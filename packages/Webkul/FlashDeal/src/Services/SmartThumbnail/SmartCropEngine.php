<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class SmartCropEngine
{
    protected float $targetRatio = 1.11258278; // 336 / 302

    protected int $targetWidth = 336;

    protected int $targetHeight = 302;

    protected float $minSubjectPreservation = 0.95; // 95% minimum subject preservation

    public function __construct(
        protected ImageAnalyzer $analyzer,
        protected WhitespaceDetector $detector
    ) {}

    /**
     * Process original image and crop/scale to target canvas.
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

        // 1. Calculate Focal Point F(Xf, Yf)
        // Xf = Xmin + 0.5 * SubjW
        // Yf = Ymin + 0.4 * SubjH (Top-Weighted 40% Offset)
        $xf = $boundingRegion['x_min'] + (0.5 * $boundingRegion['width']);
        $yf = $boundingRegion['y_min'] + (0.4 * $boundingRegion['height']);

        // 2. Candidate Crop Window calculation matching target aspect ratio
        $cropW = $origW;
        $cropH = (int) round($cropW / $targetRatio);

        if ($cropH > $origH) {
            $cropH = $origH;
            $cropW = (int) round($cropH * $targetRatio);
        }

        // Expand/contain to cover subject bounding region safely if needed
        if ($cropW < $boundingRegion['width']) {
            $cropW = min($origW, (int) round($boundingRegion['width'] * 1.15));
            $cropH = (int) round($cropW / $targetRatio);
        }

        if ($cropH < $boundingRegion['height']) {
            $cropH = min($origH, (int) round($boundingRegion['height'] * 1.15));
            $cropW = (int) round($cropH * $targetRatio);
        }

        // Clamp dimensions to image boundaries
        $cropW = min($origW, max(10, $cropW));
        $cropH = min($origH, max(10, $cropH));

        // Center Crop Window around Focal Point F(Xf, Yf)
        $cropX = (int) round($xf - ($cropW / 2));
        $cropY = (int) round($yf - ($cropH / 2));

        // Clamp offsets to image boundaries
        $cropX = max(0, min($origW - $cropW, $cropX));
        $cropY = max(0, min($origH - $cropH, $cropY));

        // 3. Execute Crop & Resize to Target Canvas via Intervention Image v3
        $croppedImage = clone $image;
        $croppedImage->crop($cropW, $cropH, $cropX, $cropY);
        $croppedImage->resize($targetW, $targetH);

        return $croppedImage;
    }
}
