<?php

namespace Webkul\FlashDeal\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Webkul\FlashDeal\Repositories\FlashDealRepository;
use Webkul\Product\Helpers\ProductImage;

class FlashDealController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected FlashDealRepository $flashDealRepository,
        protected ProductImage $productImageHelper
    ) {}

    /**
     * Get active flash deal products in JSON format for dynamic client countdown and live updates.
     */
    public function getActiveDealsJson(): JsonResponse
    {
        // Cache active flash deals for 60 seconds
        $data = Cache::remember('flash_deals_active_json', 60, function () {
            $now = Carbon::now();
            $deal = $this->flashDealRepository->getActiveDeals()->first();

            if (! $deal) {
                return [
                    'active' => false,
                    'deal' => null,
                    'products' => [],
                ];
            }

            $products = [];
            foreach ($deal->products as $dealProduct) {
                $product = $dealProduct->product;
                if (! $product || ! $product->status) {
                    continue;
                }

                $effectiveEndTime = $dealProduct->offer_end_time ?? $deal->ends_at;

                if ($effectiveEndTime && Carbon::parse($effectiveEndTime)->isPast()) {
                    continue;
                }

                $originalPrice = $product->type === 'configurable'
                    ? $product->getTypeInstance()->getMinimalPrice()
                    : $product->price;

                if (! $originalPrice || $originalPrice <= 0) {
                    $originalPrice = $product->price;
                }

                $flashPrice = $dealProduct->flash_price;
                $discountPercent = 0;
                if ($originalPrice > $flashPrice && $originalPrice > 0) {
                    $discountPercent = (int) round((($originalPrice - $flashPrice) / $originalPrice) * 100);
                }

                $cleanName = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $product->name ?? '');
                $cleanName = trim($cleanName);
                if (empty($cleanName)) {
                    $cleanName = urldecode($product->url_key ?? '');
                    $cleanName = trim(str_replace(['-', '_'], ' ', $cleanName));
                }
                if (empty($cleanName)) {
                    $cleanName = 'منتج #'.$product->id;
                }

                $products[] = [
                    'id' => $product->id,
                    'deal_product_id' => $dealProduct->id,
                    'name' => $cleanName,
                    'slug' => $product->url_key,
                    'url' => route('shop.product_or_category.index', $product->url_key),
                    'image' => $this->productImageHelper->getProductBaseImage($product)['medium_image_url'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                    'current_price' => core()->currency($flashPrice),
                    'current_price_raw' => $flashPrice,
                    'original_price' => $originalPrice > $flashPrice ? core()->currency($originalPrice) : null,
                    'original_price_raw' => $originalPrice,
                    'discount_percentage' => $discountPercent,
                    'stock' => max(1, $dealProduct->allocation_qty),
                    'sold_count' => max(0, $dealProduct->sold_qty),
                    'sold_percentage' => (int) round(($dealProduct->sold_qty / max(1, $dealProduct->allocation_qty)) * 100),
                    'offer_end_time' => $effectiveEndTime ? Carbon::parse($effectiveEndTime)->toISOString() : null,
                    'badge' => $dealProduct->badge ?? ($discountPercent >= 35 ? '🔥 الأكثر مبيعاً' : null),
                    'rating' => 4.5 + (($product->id % 5) * 0.1),
                ];
            }

            return [
                'active' => true,
                'deal' => [
                    'id' => $deal->id,
                    'title' => $deal->title ?? 'عروض خاطفة مميزة',
                    'subtitle' => $deal->subtitle ?? 'خصومات استثنائية لفترة محدودة جداً',
                    'description' => $deal->description ?? 'اكتشف أفضل المنتجات بأسعار حصريّة وتخفيضات مذهلة',
                    'banner_image' => $deal->banner_image,
                    'background_image' => $deal->background_image,
                    'accent_color' => $deal->accent_color ?? '#FFC000',
                    'secondary_color' => $deal->secondary_color ?? '#002060',
                    'promotional_message' => $deal->promotional_message ?? '🔥 وفر حتى 60% على تشكيلة من أحدث المنتجات',
                    'offer_description' => $deal->offer_description ?? 'سارع بالشراء قبل انتهاء الكمية Mخصصة للعروض الخاطفة!',
                    'view_all_url' => $deal->view_all_url ?? route('shop.home.index'),
                ],
                'products' => $products,
            ];
        });

        return response()->json($data);
    }
}
