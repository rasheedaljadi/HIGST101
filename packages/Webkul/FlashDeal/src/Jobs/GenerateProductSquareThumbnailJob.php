<?php

namespace Webkul\FlashDeal\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\FlashDeal\Services\SmartThumbnail\SmartCropEngine;
use Webkul\FlashDeal\Services\SmartThumbnail\WebpEncoder;
use Webkul\Product\Repositories\ProductRepository;

class GenerateProductSquareThumbnailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 180;

    public int $tries = 2;

    public int $timeout = 45;

    public function __construct(
        public int $productId,
        public string $sourceImagePath,
        public string $targetPath,
        public string $sourceHash
    ) {}

    public function uniqueId(): string
    {
        return 'smart_thumb_sq_'.$this->productId.'_'.substr($this->sourceHash, 0, 12);
    }

    public function handle(
        SmartCropEngine $cropEngine,
        WebpEncoder $encoder
    ): void {
        if (! file_exists($this->sourceImagePath)) {
            return;
        }

        try {
            $image = image_manager()->read($this->sourceImagePath);
            $product = app(ProductRepository::class)->find($this->productId);
            $processedImage = $cropEngine->process($image, $this->sourceImagePath, 400, 400, $product);
            $encoder->encodeAndSave($processedImage, $this->targetPath);
        } catch (\Throwable $e) {
            Log::warning('Square Smart Thumbnail generation job failed: '.$e->getMessage(), [
                'product_id' => $this->productId,
                'source' => $this->sourceImagePath,
            ]);
        }
    }
}
