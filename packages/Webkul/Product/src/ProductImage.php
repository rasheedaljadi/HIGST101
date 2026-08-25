<?php

namespace Webkul\Product;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Webkul\Customer\Contracts\Wishlist;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;

class ProductImage
{
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected ProductRepository $productRepository) {}

    /**
     * Retrieve collection of gallery images.
     *
     * @param  Product  $product
     * @return array
     */
    public function getGalleryImages($product)
    {
        if (! $product) {
            return [];
        }

        $images = [];

        foreach ($product->images as $image) {
            if (! Storage::has($image->path)) {
                continue;
            }

            $images[] = $this->getCachedImageUrls($image->path, (bool) ($image->is_local ?? false));
        }

        if (
            ! $product->parent_id
            && ! count($images)
            && ! count($product->videos ?? [])
        ) {
            $images[] = $this->getFallbackImageUrls();
        }

        /*
         * Product parent checked already above. If the case reached here that means the
         * parent is available. So recursing the method for getting the parent image if
         * images of the child are not found.
         */
        if (empty($images)) {
            $images = $this->getGalleryImages($product->parent);
        }

        return $images;
    }

    /**
     * Get product variant image if available otherwise product base image.
     *
     * @param  Wishlist  $item
     * @return array
     */
    public function getProductImage($item)
    {
        if ($item instanceof Wishlist) {
            if (isset($item->additional['selected_configurable_option'])) {
                $product = $this->productRepository->find($item->additional['selected_configurable_option']);
            } else {
                $product = $item->product;
            }
        } else {
            $product = $item->product;
        }

        return $this->getProductBaseImage($product);
    }

    /**
     * This method will first check whether the gallery images are already
     * present or not. If not then it will load from the product.
     *
     * @param  Product  $product
     * @param  array
     * @return array
     */
    public function getProductBaseImage($product, ?array $galleryImages = null)
    {
        if (! $product) {
            return;
        }

        return $galleryImages
            ? $galleryImages[0]
            : $this->otherwiseLoadFromProduct($product);
    }

    /**
     * Load product's base image.
     *
     * @param  Product  $product
     * @return array
     */
    protected function otherwiseLoadFromProduct($product)
    {
        $images = $product?->images;

        return $images && $images->count()
            ? $this->getCachedImageUrls($images[0]->path, (bool) ($images[0]->is_local ?? false))
            : $this->getFallbackImageUrls();
    }

    /**
     * Get cached urls configured for intervention package.
     *
     * @param  string  $path
     */
    private function getCachedImageUrls($path, bool $isLocal = false): array
    {
        $url = Storage::url($path);

        return [
            'small_image_url' => $url,
            'medium_image_url' => $url,
            'large_image_url' => $url,
            'original_image_url' => $url,
            'fallback_url' => $url,
            'is_local' => $isLocal,
        ];
    }

    /**
     * Get cached image URL, generating the cached file if missing or falling back to storage URL.
     */
    private function getOrGenerateCachedUrl(string $template, string $path): string
    {
        $cachedPath = public_path('cache/'.$template.'/'.$path);

        if (file_exists($cachedPath)) {
            return url('cache/'.$template.'/'.$path);
        }

        $originalPath = storage_path('app/public/'.$path);

        if (file_exists($originalPath)) {
            try {
                $targetDir = dirname($cachedPath);

                if (! file_exists($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }

                if ($template === 'original') {
                    @copy($originalPath, $cachedPath);
                } else {
                    $templates = config('imagecache.templates', []);
                    $templateClass = $templates[$template] ?? null;

                    if ($templateClass && class_exists($templateClass)) {
                        $filter = new $templateClass;

                        if (method_exists($filter, 'applyFilter')) {
                            $image = image_manager()->read($originalPath);
                            $image = $filter->applyFilter($image);
                            @file_put_contents($cachedPath, (string) $image->encodeByMediaType());
                        } else {
                            @copy($originalPath, $cachedPath);
                        }
                    } else {
                        @copy($originalPath, $cachedPath);
                    }
                }

                if (file_exists($cachedPath)) {
                    return url('cache/'.$template.'/'.$path);
                }
            } catch (\Throwable) {
                // Ignore generation failure and fall through to Storage::url
            }
        }

        return Storage::url($path);
    }

    /**
     * Get fallback urls.
     */
    private function getFallbackImageUrls(): array
    {
        $smallImageUrl = core()->getConfigData('catalog.products.cache_small_image.url')
                        ? Storage::url(core()->getConfigData('catalog.products.cache_small_image.url'))
                        : bagisto_asset('images/small-product-placeholder.webp', 'shop');

        $mediumImageUrl = core()->getConfigData('catalog.products.cache_medium_image.url')
                        ? Storage::url(core()->getConfigData('catalog.products.cache_medium_image.url'))
                        : bagisto_asset('images/medium-product-placeholder.webp', 'shop');

        $largeImageUrl = core()->getConfigData('catalog.products.cache_large_image.url')
                        ? Storage::url(core()->getConfigData('catalog.products.cache_large_image.url'))
                        : bagisto_asset('images/large-product-placeholder.webp', 'shop');

        return [
            'small_image_url' => $smallImageUrl,
            'medium_image_url' => $mediumImageUrl,
            'large_image_url' => $largeImageUrl,
            'original_image_url' => bagisto_asset('images/large-product-placeholder.webp', 'shop'),
            'fallback_url' => bagisto_asset('images/large-product-placeholder.webp', 'shop'),
        ];
    }

    /**
     * Is driver local.
     */
    private function isDriverLocal(): bool
    {
        return Storage::getAdapter() instanceof LocalFilesystemAdapter;
    }
}
