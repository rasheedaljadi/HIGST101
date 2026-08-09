<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class ImageEnhancer
{
    /**
     * Apply subtle quality enhancements to processed image.
     *
     * Enhancements are intentionally light to avoid over-processing.
     */
    public function enhance(ImageInterface $image): ImageInterface
    {
        // 1. Light sharpening to bring out product details
        $image->sharpen(8);

        // 2. Slight brightness lift for vibrancy
        $image->brightness(2);

        // 3. Subtle contrast boost
        $image->contrast(5);

        return $image;
    }
}
