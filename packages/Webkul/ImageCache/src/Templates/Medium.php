<?php

namespace Webkul\ImageCache\Templates;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Medium image template filter.
 *
 * Creates images at 300x300 pixels.
 */
class Medium
{
    /**
     * The width for medium images.
     */
    protected int $width = 400;

    /**
     * The height for medium images.
     */
    protected int $height = 500;

    /**
     * Apply the filter to the image.
     */
    public function applyFilter(ImageInterface $image): ImageInterface
    {
        return $image->cover($this->width, $this->height);
    }

    /**
     * Get the configured width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the configured height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }
}
