<?php

namespace Webkul\FlashDeal\Helpers;

use Illuminate\Support\Facades\Storage;
use Webkul\FlashDeal\Jobs\GenerateQuickOfferSmartThumbnailJob;
use Webkul\Product\Contracts\Product;

class SmartThumbnailHelper
{
    protected string $version = 'v1';

    protected string $configHash = 'c82_m15_t28'; // Config parameter signature

    /**
     * Get Quick Offer Smart Thumbnail URL for product, falling back to original image URL.
     */
    public function getQuickOfferThumbnailUrl(?Product $product, string $fallbackUrl): string
    {
        if (! $product) {
            return $fallbackUrl;
        }

        $baseImage = $product->images->first();

        if (! $baseImage || ! $baseImage->path) {
            return $fallbackUrl;
        }

        $sourcePath = storage_path('app/public/'.$baseImage->path);

        if (! file_exists($sourcePath)) {
            return $fallbackUrl;
        }

        $sourceHash = md5_file($sourcePath) ?: md5($sourcePath);
        $cacheKey = md5($product->id.'_'.$sourceHash.'_'.$this->version.'_'.$this->configHash);
        $subDir = substr($cacheKey, 0, 2);

        $relativePath = 'smart-thumbnails/quick_offers/v1/'.$subDir.'/quick-offer-v1-'.$product->id.'-'.substr($cacheKey, 0, 12).'.webp';
        $fullTargetPath = storage_path('app/public/'.$relativePath);

        // Check if thumbnail exists in public storage
        if (file_exists($fullTargetPath)) {
            return Storage::url($relativePath);
        }

        // Check public/cache folder fallback
        $publicCachePath = public_path('cache/'.$relativePath);
        if (file_exists($publicCachePath)) {
            return url('cache/'.$relativePath);
        }

        // Dispatch background job to generate missing thumbnail
        try {
            GenerateQuickOfferSmartThumbnailJob::dispatch(
                $product->id,
                $sourcePath,
                $fullTargetPath,
                $sourceHash
            );
        } catch (\Throwable) {
            // Queue dispatch fallback to direct URL
        }

        return $fallbackUrl;
    }
}
