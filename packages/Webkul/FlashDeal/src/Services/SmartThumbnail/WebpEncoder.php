<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Intervention\Image\Interfaces\ImageInterface;

class WebpEncoder
{
    protected int $quality = 82;

    /**
     * Encode processed image to WebP and save to target path.
     */
    public function encodeAndSave(ImageInterface $image, string $targetPath): bool
    {
        $targetDir = dirname($targetPath);

        if (! file_exists($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        try {
            $encoded = (string) $image->toWebp($this->quality);

            return (bool) file_put_contents($targetPath, $encoded);
        } catch (\Throwable) {
            try {
                $encoded = (string) $image->encodeByMediaType();

                return (bool) file_put_contents($targetPath, $encoded);
            } catch (\Throwable) {
                return false;
            }
        }
    }
}
