@props([
    'product' => null,
    'dealProduct' => null,
    'index' => 0,
])

@php
    $productImageHelper = app(\Webkul\Product\ProductImage::class);
    
    $productEntity = $product ?? $dealProduct?->product;
    if (! $productEntity) return;

    $imageUrl = $productImageHelper->getProductBaseImage($productEntity)['medium_image_url'] ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop');
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

    $isBestSeller = ($index === 2) || ($discountPercent >= 35) || ($dealProduct?->badge === '🔥 الأكثر مبيعاً');
    $badgeText = $dealProduct?->badge ?? ($isBestSeller ? 'الأكثر مبيعاً' : null);
    $endTime = $dealProduct?->offer_end_time ?? $dealProduct?->deal?->ends_at;
@endphp

<div 
    x-data="{ expired: false }"
    x-show="!expired"
    x-transition:leave="transition ease-in duration-300 transform scale-95 opacity-0"
    @countdown-expired.window="$event.detail.productId == '{{ $productEntity->id }}' ? expired = true : null"
    class="w-full bg-white dark:bg-gray-900 rounded-2xl sm:rounded-3xl p-2.5 sm:p-3.5 shadow-sm hover:shadow-md transition-all relative border border-gray-100 dark:border-gray-800 flex flex-col justify-between overflow-hidden min-h-[260px] sm:min-h-[320px]"
>
    <!-- Featured "Best Seller" Badge -->
    @if ($badgeText || $isBestSeller)
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#001A54] text-[#FFC000] font-bold px-2.5 sm:px-4 py-0.5 sm:py-1 rounded-full text-[10px] sm:text-xs flex items-center gap-1 whitespace-nowrap shadow-md z-20">
            <span>{{ $badgeText ?? 'الأكثر مبيعاً' }}</span>
            <span>🔥</span>
        </div>
    @endif

    <!-- Top Header Bar: Limited Offer Badge & Countdown -->
    <div class="flex justify-between items-center mb-1.5 sm:mb-2 {{ ($badgeText || $isBestSeller) ? 'mt-2' : '' }}">
        <div class="bg-[#FFF9E6] dark:bg-amber-950/40 text-[#D97706] dark:text-amber-400 font-bold px-2 sm:px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] flex items-center gap-1">
            <span>⚡</span>
            <span class="hidden sm:inline">عرض محدود</span>
        </div>

        @include('flash_deal::shop.components.countdown', [
            'endTime' => $endTime,
            'productId' => $productEntity->id,
        ])
    </div>

    <!-- Product Image (4:5 Aspect Ratio with Top-Left Discount Badge) -->
    <div class="relative w-full mb-2 rounded-xl sm:rounded-2xl overflow-hidden group shrink-0">
        @if ($discountPercent > 0)
            <span class="absolute top-2 left-2 z-10 bg-[#FFC000] text-black font-extrabold px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl text-[10px] sm:text-xs shadow-sm">
                -{{ $discountPercent }}%
            </span>
        @endif

        <a 
            href="{{ $productUrl }}" 
            class="w-full flex items-center justify-center bg-gray-50/80 dark:bg-gray-800/40 overflow-hidden aspect-[4/5]"
        >
            <img 
                src="{{ $imageUrl }}" 
                alt="{{ $cleanName }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
            />
        </a>
    </div>

    <!-- Product Title -->
    <a href="{{ $productUrl }}" class="block mb-2 px-0.5">
        <h3 
            class="text-xs sm:text-sm md:text-base font-bold text-[#001A54] dark:text-gray-100 hover:text-[#FFC000] transition-colors text-center leading-tight sm:leading-relaxed" 
            title="{{ $cleanName }}"
            style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; max-height: 2.6rem;"
        >
            {{ $cleanName }}
        </h3>
    </a>

    <!-- Bottom Footer Bar: Cart Icon Button + Price -->
    <div class="border-t border-gray-100 dark:border-gray-800 pt-2 sm:pt-3 mt-auto flex items-center justify-between gap-1.5 sm:gap-3">
        <!-- Cart Icon Button -->
        <a 
            href="{{ $productUrl }}" 
            class="bg-[#FFC000] hover:bg-yellow-500 text-black font-bold p-2 sm:p-3 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-sm shrink-0 w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 transition-transform active:scale-95"
            title="أضف للسلة"
            aria-label="أضف للسلة"
        >
            <svg class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 text-black" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </a>

        <!-- Vertical Divider (hidden on small mobile screens) -->
        <div class="hidden sm:block h-6 sm:h-8 w-[1px] bg-gray-200 dark:bg-gray-700 shrink-0"></div>

        <!-- Pricing Section -->
        <div class="flex flex-col sm:flex-row items-end sm:items-baseline gap-0.5 sm:gap-2 overflow-hidden text-left" dir="ltr">
            <span class="text-sm sm:text-lg md:text-xl font-black text-[#001A54] dark:text-[#FFC000] truncate">
                {{ core()->currency($flashPrice) }}
            </span>
            @if ($originalPrice > $flashPrice)
                <span class="text-gray-400 line-through text-[10px] sm:text-xs font-semibold shrink-0">
                    {{ core()->currency($originalPrice) }}
                </span>
            @endif
        </div>
    </div>
</div>
