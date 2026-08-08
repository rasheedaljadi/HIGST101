<?php

namespace Webkul\FlashDeal\Console\Commands;

use Illuminate\Console\Command;
use Webkul\FlashDeal\Helpers\SmartThumbnailHelper;
use Webkul\FlashDeal\Repositories\FlashDealRepository;
use Webkul\Product\ProductImage;

class RegenerateSmartThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-thumbnail:regenerate {--scope=quick_offers : Scope context}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Smart Thumbnails for Quick Deals products';

    /**
     * Execute the console command.
     */
    public function handle(
        FlashDealRepository $flashDealRepository,
        SmartThumbnailHelper $helper
    ): int {
        $this->info('Starting Smart Thumbnail V1 generation...');

        $activeDeals = $flashDealRepository->getActiveDeals();

        if ($activeDeals->isEmpty()) {
            $this->warn('No active Flash Deals found.');

            return 0;
        }

        $count = 0;

        foreach ($activeDeals as $deal) {
            foreach ($deal->products as $dealProduct) {
                $product = $dealProduct->product;
                if (! $product) {
                    continue;
                }

                $productImageHelper = app(ProductImage::class);
                $fallbackUrl = $productImageHelper->getProductBaseImage($product)['medium_image_url'] ?? '';

                $url = $helper->getQuickOfferThumbnailUrl($product, $fallbackUrl);
                $this->line("Dispatched Smart Thumbnail job for Product #{$product->id}");
                $count++;
            }
        }

        $this->info("Completed! Dispatched {$count} Smart Thumbnail generation jobs.");

        return 0;
    }
}
