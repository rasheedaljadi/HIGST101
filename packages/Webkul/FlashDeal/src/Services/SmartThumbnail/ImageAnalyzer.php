<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class ImageAnalyzer
{
    /**
     * Analyze image properties and metadata.
     */
    public function analyze(ImageInterface $image, string $filePath): array
    {
        $width = $image->width();
        $height = $image->height();
        $aspectRatio = $height > 0 ? $width / $height : 1.0;
        $hash = file_exists($filePath) ? md5_file($filePath) : md5($filePath);

        $orientation = $this->determineOrientation($aspectRatio);

        return [
            'width' => $width,
            'height' => $height,
            'aspect_ratio' => $aspectRatio,
            'orientation' => $orientation,
            'file_hash' => $hash,
            'file_path' => $filePath,
        ];
    }

    /**
     * Categorize image orientation.
     */
    protected function determineOrientation(float $ratio): string
    {
        if ($ratio > 1.60) {
            return 'extreme_landscape';
        }

        if ($ratio > 1.05) {
            return 'landscape';
        }

        if ($ratio >= 0.95) {
            return 'square';
        }

        if ($ratio >= 0.65) {
            return 'portrait';
        }

        return 'extreme_portrait';
    }
}
