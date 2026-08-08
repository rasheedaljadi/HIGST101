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

class GenerateQuickOfferSmartThumbnailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 180;

    public int $tries = 2;

    public int $timeout = 30;

    public function __construct(
        public int $productId,
        public string $sourceImagePath,
        public string $targetPath,
        public string $sourceHash
    ) {}

    public function uniqueId(): string
    {
        return 'smart_thumb_qo_'.$this->productId.'_'.substr($this->sourceHash, 0, 12).'_v1';
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
            $processedImage = $cropEngine->process($image, $this->sourceImagePath);
            $encoder->encodeAndSave($processedImage, $this->targetPath);
        } catch (\Throwable $e) {
            Log::warning('Smart Thumbnail generation failed: '.$e->getMessage(), [
                'product_id' => $this->productId,
                'source' => $this->sourceImagePath,
            ]);
        }
    }
}
