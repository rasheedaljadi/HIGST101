<?php

namespace Webkul\FlashDeal\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Contracts\Product;

class SmartThumbnailHelper
{
    protected string $version = 'v1';

    protected string $configHash = 'c82_m15_t28'; // Config parameter signature

    /**
     * Check if the Smart Thumbnail Engine is globally enabled.
     */
    public function isEngineActive(): bool
    {
        $active = core()->getConfigData('catalog.smart_thumbnail.settings.active');

        return $active === null ? true : (bool) $active;
    }

    /**
     * Check if the Quick Offers section smart thumbnails are enabled.
     */
    public function isQuickOffersActive(): bool
    {
        if (! $this->isEngineActive()) {
            return false;
        }

        $active = core()->getConfigData('catalog.smart_thumbnail.settings.quick_offers_active');

        return $active === null ? true : (bool) $active;
    }

    /**
     * Check if the Product Page gallery smart thumbnails are enabled.
     */
    public function isProductPageActive(): bool
    {
        if (! $this->isEngineActive()) {
            return false;
        }

        $active = core()->getConfigData('catalog.smart_thumbnail.settings.product_page_active');

        return $active === null ? true : (bool) $active;
    }

    /**
     * Get Quick Offer Smart Thumbnail URL for product, falling back to original image URL.
     */
    public function getQuickOfferThumbnailUrl(?Product $product, string $fallbackUrl): string
    {
        if (! $product || ! $this->isQuickOffersActive()) {
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

        // Try inline generation for immediate display if missing
        try {
            $imageManager = image_manager();
            $cropEngine = app(SmartCropEngine::class);
            $encoder = app(WebpEncoder::class);

            $img = $imageManager->read($sourcePath);
            $processed = $cropEngine->process($img, $sourcePath);
            $encoder->encodeAndSave($processed, $fullTargetPath);

            if (file_exists($fullTargetPath)) {
                return Storage::url($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('Quick Offer Smart Thumbnail inline generation failed: '.$e->getMessage(), [
                'product_id' => $product->id,
                'source' => $sourcePath,
            ]);
        }

        return $fallbackUrl;
    }

    /**
     * Get Product Detail Smart Thumbnail URL for main image, falling back to original image URL.
     */
    public function getProductDetailThumbnailUrl(?Product $product, string $fallbackUrl, bool $isRetina = false): string
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

        $targetW = $isRetina ? 896 : 448;
        $targetH = $isRetina ? 1120 : 560;
        $retinaTag = $isRetina ? '_retina' : '_std';

        $sourceHash = md5_file($sourcePath) ?: md5($sourcePath);
        $cacheKey = md5($product->id.'_'.$sourceHash.'_'.$this->version.'_pdp_'.$targetW.'x'.$targetH.'_'.$this->configHash);
        $subDir = substr($cacheKey, 0, 2);

        $relativePath = 'smart-thumbnails/product_detail/v1/'.$subDir.'/pdp-v1-'.$product->id.'-'.substr($cacheKey, 0, 12).$retinaTag.'.webp';
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

        // Try inline generation for immediate display if missing
        try {
            $imageManager = image_manager();
            $cropEngine = app(SmartCropEngine::class);
            $encoder = app(WebpEncoder::class);

            $img = $imageManager->read($sourcePath);
            $processed = $cropEngine->process($img, $sourcePath, $targetW, $targetH);
            $encoder->encodeAndSave($processed, $fullTargetPath);

            if (file_exists($fullTargetPath)) {
                return Storage::url($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('PDP Smart Thumbnail inline generation failed: '.$e->getMessage(), [
                'product_id' => $product->id,
                'source' => $sourcePath,
            ]);
        }

        return $fallbackUrl;
    }

    /**
     * Get Product Detail Smart Thumbnail URL for 5:4 test context (560x448), falling back to original image URL.
     */
    public function getProductDetailTest5x4ThumbnailUrl(?Product $product, string $fallbackUrl): string
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

        $targetW = 560;
        $targetH = 448;

        $sourceHash = md5_file($sourcePath) ?: md5($sourcePath);
        $cacheKey = md5($product->id.'_'.$sourceHash.'_'.$this->version.'_pdp_test_5x4_'.$targetW.'x'.$targetH.'_'.$this->configHash);
        $subDir = substr($cacheKey, 0, 2);

        $relativePath = 'smart-thumbnails/product_detail/v1_test_5x4/'.$subDir.'/pdp-v1-test5x4-'.$product->id.'-'.substr($cacheKey, 0, 12).'.webp';
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

        // Try inline generation for immediate display if missing
        try {
            $imageManager = image_manager();
            $cropEngine = app(SmartCropEngine::class);
            $encoder = app(WebpEncoder::class);

            $img = $imageManager->read($sourcePath);
            $processed = $cropEngine->process($img, $sourcePath, $targetW, $targetH);
            $encoder->encodeAndSave($processed, $fullTargetPath);

            if (file_exists($fullTargetPath)) {
                return Storage::url($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('PDP Smart Thumbnail inline generation failed: '.$e->getMessage(), [
                'product_id' => $product->id,
                'source' => $sourcePath,
            ]);
        }

        return $fallbackUrl;
    }

    /**
     * Get Product Detail Smart Thumbnail URL for any specific image in product gallery.
     */
    public function getProductDetailTest5x4ThumbnailForImage(?Product $product, $imageModel, string $fallbackUrl): string
    {
        if (! $product || ! $imageModel || ! isset($imageModel->path) || ! $imageModel->path) {
            return $fallbackUrl;
        }

        $sourcePath = storage_path('app/public/'.$imageModel->path);

        if (! file_exists($sourcePath)) {
            return $fallbackUrl;
        }

        $targetW = 560;
        $targetH = 448;

        $imageId = $imageModel->id ?? 'idx';
        $sourceHash = md5_file($sourcePath) ?: md5($sourcePath);
        $cacheKey = md5($product->id.'_'.$imageId.'_'.$sourceHash.'_'.$this->version.'_pdp_test_5x4_'.$targetW.'x'.$targetH.'_'.$this->configHash);
        $subDir = substr($cacheKey, 0, 2);

        $relativePath = 'smart-thumbnails/product_detail/v1_test_5x4/'.$subDir.'/pdp-v1-test5x4-'.$product->id.'-'.$imageId.'-'.substr($cacheKey, 0, 12).'.webp';
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

        // Try inline generation for immediate display if missing
        try {
            $imageManager = image_manager();
            $cropEngine = app(SmartCropEngine::class);
            $encoder = app(WebpEncoder::class);

            $img = $imageManager->read($sourcePath);
            $processed = $cropEngine->process($img, $sourcePath, $targetW, $targetH);
            $encoder->encodeAndSave($processed, $fullTargetPath);

            if (file_exists($fullTargetPath)) {
                return Storage::url($relativePath);
            }
        } catch (\Throwable $e) {
            Log::warning('PDP Smart Thumbnail inline generation failed: '.$e->getMessage(), [
                'product_id' => $product->id,
                'source' => $sourcePath,
            ]);
        }

        return $fallbackUrl;
    }
}
