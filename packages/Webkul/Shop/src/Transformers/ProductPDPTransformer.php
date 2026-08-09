<?php

namespace Webkul\Shop\Transformers;

use Illuminate\Support\Str;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Helpers\Review as ReviewHelper;
use Webkul\Product\Helpers\View as ProductViewHelper;

class ProductPDPTransformer
{
    /**
     * Create a new transformer instance.
     *
     * @return void
     */
    public function __construct(
        protected ReviewHelper $reviewHelper,
        protected ProductViewHelper $productViewHelper
    ) {}

    /**
     * Transform Eloquent product model into a structured PDP ViewModel payload.
     *
     * @param  Product  $product
     */
    public function transform($product): array
    {
        if (! $product) {
            return [];
        }

        $typeInstance = $product->getTypeInstance();
        $baseImage = product_image()->getProductBaseImage($product);
        $galleryImages = product_image()->getGalleryImages($product);
        $videos = product_video()->getVideos($product);

        $avgRatings = $this->reviewHelper->getAverageRating($product);
        $totalRatings = $this->reviewHelper->getTotalFeedback($product);
        $percentageRatings = $this->reviewHelper->getPercentageRating($product);

        $customAttributeValues = $this->productViewHelper->getAdditionalData($product);
        $dropshipping = $this->productViewHelper->getDropshippingMetadata($product);

        $totalQty = 0;
        if ($product->type === 'simple') {
            $totalQty = $product->inventories()->sum('qty');
        } elseif ($product->type === 'configurable') {
            foreach ($product->variants as $variant) {
                $totalQty += $variant->inventories()->sum('qty');
            }
        }

        $isSaleable = (bool) $product->isSaleable(1);

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'type' => $product->type,
            'name' => $product->name,
            'url_key' => $product->url_key,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'meta_title' => trim((string) $product->meta_title) !== '' ? $product->meta_title : $product->name,
            'meta_description' => trim((string) $product->meta_description) !== ''
                ? $product->meta_description
                : Str::limit(strip_tags((string) $product->description), 120, ''),
            'meta_keywords' => $product->meta_keywords,
            'is_saleable' => $isSaleable,
            'total_qty' => $totalQty,
            'in_stock' => $isSaleable && ($totalQty > 0 || ! $typeInstance->showQuantityBox()),
            'price_html' => $typeInstance->getPriceHtml(),
            'minimal_price' => $typeInstance->getMinimalPrice(),
            'base_image' => $baseImage,
            'gallery_images' => $galleryImages,
            'videos' => $videos,
            'ratings' => [
                'average' => $avgRatings,
                'total' => $totalRatings,
                'percentages' => $percentageRatings,
            ],
            'custom_attributes' => $customAttributeValues,
            'dropshipping' => $dropshipping,
            'model' => $product,
        ];
    }
}
