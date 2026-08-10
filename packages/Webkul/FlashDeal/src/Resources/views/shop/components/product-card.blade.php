@props([
    'product' => null,
    'dealProduct' => null,
    'index' => 0,
])

@php
    $productImageHelper = app(\Webkul\Product\ProductImage::class);
    $smartThumbnailHelper = app(\Webkul\FlashDeal\Helpers\SmartThumbnailHelper::class);
    
    $productEntity = $product ?? $dealProduct?->product;
    if (! $productEntity) return;

    $fallbackImageUrl = $productImageHelper->getProductBaseImage($productEntity)['medium_image_url'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop');
    $imageUrl = $smartThumbnailHelper->getQuickOfferThumbnailUrl($productEntity, $fallbackImageUrl);

    $productUrl = route('shop.product_or_category.index', $productEntity->url_key);

    $cleanName = preg_replace('/[^\p{L}\p{N}\s\-\_]/u', '', $productEntity->name ?? '');
    $cleanName = trim($cleanName);
    if (empty($cleanName)) {
        $cleanName = urldecode($productEntity->url_key ?? '');
        $cleanName = trim(str_replace(['-', '_'], ' ', $cleanName));
    }
    if (empty($cleanName)) {
        $cleanName = 'منتج #' . $productEntity->id;
    }

    $originalPrice = $productEntity->type === 'configurable' 
        ? $productEntity->getTypeInstance()->getMinimalPrice() 
        : $productEntity->price;

    if (! $originalPrice || $originalPrice <= 0) {
        $originalPrice = $productEntity->price;
    }

    $flashPrice = $dealProduct?->flash_price ?? $productEntity->special_price ?? $productEntity->price;

    $discountPercent = 0;
    if ($originalPrice > $flashPrice && $originalPrice > 0) {
        $discountPercent = (int) round((($originalPrice - $flashPrice) / $originalPrice) * 100);
    }

    $endTime = $dealProduct?->effective_end_time ?? $dealProduct?->offer_end_time ?? $dealProduct?->deal?->ends_at;
@endphp

<!-- Product Card: Fixed 310px x 505px Desktop Reference Dimensions -->
<div 
    x-data="{ expired: false }"
    x-show="!expired"
    x-transition:leave="transition ease-in duration-300 transform scale-95 opacity-0"
    @countdown-expired.window="$event.detail.productId == '{{ $productEntity->id }}' ? expired = true : null"
    class="w-full h-[505px] max-h-[505px] bg-white dark:bg-gray-900 rounded-2xl sm:rounded-3xl p-3 shadow-sm hover:shadow-md transition-all relative border border-gray-100 dark:border-gray-800 flex flex-col justify-between overflow-hidden box-border shrink-0 select-none"
>
    <!-- Top Header Area: Countdown Timer Only (Best Seller & Lightning Badges Removed) -->
    <div class="flex items-center justify-between h-[36px] mb-2 shrink-0">
        <div class="flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400">
            <span>ينتهي خلال</span>
        </div>

        @include('flash_deal::shop.components.countdown', [
            'endTime' => $endTime,
            'productId' => $productEntity->id,
        ])
    </div>

    <!-- Product Image Container: Fixed 336 / 302 Aspect Ratio (~1.113:1), object-cover -->
    <div 
        class="relative w-full aspect-[336/302] rounded-xl sm:rounded-2xl overflow-hidden bg-gray-50/80 dark:bg-gray-800/40 shrink-0 group mb-2"
        style="aspect-ratio: 336 / 302;"
    >
        @if ($discountPercent > 0)
            <span class="absolute top-2.5 right-2.5 z-10 bg-[#FFC000] text-black font-black px-2 sm:px-2.5 py-1 rounded-lg sm:rounded-xl text-[10px] sm:text-xs shadow-md">
                -{{ $discountPercent }}%
            </span>
        @endif

        <a 
            href="{{ $productUrl }}" 
            class="w-full h-full block overflow-hidden"
        >
            <img 
                src="{{ $imageUrl }}" 
                alt="{{ $cleanName }}"
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300 block"
                loading="lazy"
                onerror="this.onerror=null;this.src='{{ $fallbackImageUrl }}';"
            />
        </a>
    </div>

    <!-- Product Title Area: Fixed Height (~56px), Clamped to 2 Lines Max -->
    <a href="{{ $productUrl }}" class="block h-[56px] flex items-center mb-2 px-0.5 shrink-0 overflow-hidden">
        <h3 
            class="text-xs sm:text-sm font-bold text-[#001A54] dark:text-gray-100 hover:text-[#FFC000] transition-colors text-center w-full leading-tight sm:leading-snug" 
            title="{{ $cleanName }}"
            style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; max-height: 2.8rem;"
        >
            {{ $cleanName }}
        </h3>
    </a>

    <!-- Bottom Footer Area: Fixed Height (~64px), Price & Cart Button -->
    <div class="border-t border-gray-100 dark:border-gray-800 pt-2.5 mt-auto h-[64px] flex items-center justify-between gap-2 shrink-0">
        <!-- Pricing Section -->
        <div class="flex flex-col items-start justify-center overflow-hidden">
            <span class="text-sm sm:text-base md:text-lg font-black text-[#001A54] dark:text-[#FFC000] truncate">
                {{ core()->currency($flashPrice) }}
            </span>
            @if ($originalPrice > $flashPrice)
                <span class="text-gray-400 line-through text-[10px] sm:text-xs font-semibold truncate">
                    {{ core()->currency($originalPrice) }}
                </span>
            @else
                <span class="h-3 block"></span>
            @endif
        </div>

        <!-- Cart Icon Button -->
        <a 
            href="{{ $productUrl }}" 
            class="bg-navyBlue hover:opacity-90 text-white font-bold p-2 sm:p-2.5 rounded-full flex items-center justify-center shadow-md shrink-0 w-9 h-9 sm:w-11 sm:h-11 transition-transform active:scale-95"
            style="background-color: #060C3B !important; color: #ffffff !important;"
            title="أضف للسلة"
            aria-label="أضف للسلة"
        >
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="color: #ffffff !important;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </a>
    </div>
</div>
