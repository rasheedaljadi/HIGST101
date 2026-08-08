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

class GenerateProductDetailSmartThumbnailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 180;

    public int $tries = 2;

    public int $timeout = 35;

    public function __construct(
        public int $productId,
        public string $sourceImagePath,
        public string $targetPath,
        public string $sourceHash,
        public int $targetWidth = 448,
        public int $targetHeight = 560,
        public bool $isRetina = false
    ) {}

    public function uniqueId(): string
    {
        $suffix = $this->isRetina ? '_retina' : '_std';

        return 'smart_thumb_pdp_'.$this->productId.'_'.substr($this->sourceHash, 0, 12).'_v1'.$suffix;
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
            $processedImage = $cropEngine->process($image, $this->sourceImagePath, $this->targetWidth, $this->targetHeight);
            $encoder->encodeAndSave($processedImage, $this->targetPath);
        } catch (\Throwable $e) {
            Log::warning('Product Detail Smart Thumbnail generation failed: '.$e->getMessage(), [
                'product_id' => $this->productId,
                'source' => $this->sourceImagePath,
            ]);
        }
    }
}
