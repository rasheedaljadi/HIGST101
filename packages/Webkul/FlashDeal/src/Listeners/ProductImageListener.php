<?php

namespace Webkul\FlashDeal\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\FlashDeal\Jobs\GenerateProductSquareThumbnailJob;
use Webkul\Product\Contracts\Product;

class ProductImageListener
{
    /**
     * Handle after product save event.
     */
    public function afterProductSave(Product $product): void
    {
        try {
            $baseImage = $product->images->first();
            if (! $baseImage || ! $baseImage->path) {
                return;
            }

            $sourcePath = storage_path('app/public/'.$baseImage->path);
            if (! file_exists($sourcePath)) {
                return;
            }

            $sourceHash = md5_file($sourcePath) ?: md5($sourcePath);
            $cacheKey = md5($product->id.'_'.$sourceHash.'_1x1_v2');
            $subDir = substr($cacheKey, 0, 2);
            $relativePath = 'smart-thumbnails/square_cards/v2/'.$subDir.'/card-'.$product->id.'-'.substr($cacheKey, 0, 12).'.webp';
            $fullTargetPath = storage_path('app/public/'.$relativePath);

            // If already generated, do nothing
            if (file_exists($fullTargetPath)) {
                return;
            }

            // Dispatch asynchronous job to process and square in background
            GenerateProductSquareThumbnailJob::dispatch(
                $product->id,
                $sourcePath,
                $fullTargetPath,
                $sourceHash
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch Product Square Thumbnail Job: '.$e->getMessage(), [
                'product_id' => $product->id,
            ]);
        }
    }
}
