<?php

namespace Webkul\FlashDeal\Console\Commands;

use Illuminate\Console\Command;
use Webkul\FlashDeal\Helpers\SmartThumbnailHelper;
use Webkul\Product\ProductImage;
use Webkul\Product\Repositories\ProductRepository;

class RegenerateProductDetailSmartThumbnailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-thumbnail:regenerate-pdp {--product_id= : Product ID filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Product Detail Smart Thumbnails for PDP main images';

    /**
     * Execute the console command.
     */
    public function handle(
        ProductRepository $productRepository,
        SmartThumbnailHelper $helper
    ): int {
        $this->info('Starting Product Detail Smart Thumbnail V1 generation...');

        $productId = $this->option('product_id');

        if ($productId) {
            $products = $productRepository->where('id', $productId)->get();
        } else {
            $products = $productRepository->where('status', 1)->limit(100)->get();
        }

        if ($products->isEmpty()) {
            $this->warn('No products found.');

            return 0;
        }

        $count = 0;

        foreach ($products as $product) {
            $productImageHelper = app(ProductImage::class);
            $fallbackUrl = $productImageHelper->getProductBaseImage($product)['large_image_url'] ?? '';

            $url = $helper->getProductDetailThumbnailUrl($product, $fallbackUrl);
            $this->line("Dispatched PDP Smart Thumbnail job for Product #{$product->id}");
            $count++;
        }

        $this->info("Completed! Dispatched {$count} PDP Smart Thumbnail generation jobs.");

        return 0;
    }
}
