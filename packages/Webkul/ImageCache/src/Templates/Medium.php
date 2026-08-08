<?php

namespace Webkul\ImageCache\Templates;

use Intervention\Image\Interfaces\ImageInterface;

/**
 * Medium image template filter.
 *
 * Creates images at 400x500 pixels (4:5 aspect ratio).
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
        return $image->scale($this->width, $this->height);
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
