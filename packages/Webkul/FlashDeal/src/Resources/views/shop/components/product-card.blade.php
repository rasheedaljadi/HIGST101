@props([
    'product' => null,
    'dealProduct' => null,
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

    $stock = max(1, $dealProduct?->allocation_qty ?? 10);
    $soldCount = max(0, $dealProduct?->sold_qty ?? 0);
    $badgeText = $dealProduct?->badge ?? ($discountPercent >= 35 ? '🔥 الأكثر مبيعاً' : null);
    $endTime = $dealProduct?->offer_end_time ?? $dealProduct?->deal?->ends_at;
@endphp

<div 
    x-data="{ expired: false }"
    x-show="!expired"
    x-transition:leave="transition ease-in duration-300 transform scale-95 opacity-0"
    @countdown-expired.window="if ($event.detail.productId == '{{ $productEntity->id }}') expired = true"
    class="w-full flex flex-col justify-between bg-white dark:bg-gray-900 rounded-3xl p-4 shadow-md hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative group border border-gray-100 dark:border-gray-800 text-gray-900 dark:text-white overflow-hidden"
>
    <!-- Dynamic Badge if active -->
    @if ($badgeText)
        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#002060] text-[#FFC000] text-[10px] font-black px-3 py-0.5 rounded-full border border-[#FFC000]/40 shadow-md z-20 flex items-center gap-1 whitespace-nowrap">
            <span>{{ $badgeText }}</span>
        </div>
    @endif

    <!-- Top Row: Discount Pill & Live Countdown Timer -->
    <div class="flex items-center justify-between w-full mb-3 pt-1 z-10">
        <!-- Discount Badge -->
        @if ($discountPercent > 0)
            <div class="bg-[#FFC000] text-gray-950 text-[11px] font-black px-2.5 py-0.5 rounded-full shadow-sm">
                -{{ $discountPercent }}%
            </div>
        @else
            <div></div>
        @endif

        <!-- Countdown Timer -->
        <x-flash_deal::shop.components.countdown 
            :end-time="$endTime"
            :product-id="$productEntity->id"
        />
    </div>

    <!-- Product Image Container -->
    <a href="{{ $productUrl }}" class="block w-full h-36 md:h-40 rounded-2xl overflow-hidden bg-gray-50 dark:bg-gray-800 relative mb-3 group-hover:scale-105 transition-transform duration-300 flex items-center justify-center p-2">
        <img 
            src="{{ $imageUrl }}" 
            alt="{{ $cleanName }}"
            class="w-full h-full object-contain"
            loading="lazy"
        />
    </a>

    <!-- Product Title -->
    <a href="{{ $productUrl }}" class="block mb-1 text-right">
        <h3 class="text-xs md:text-sm font-bold text-gray-900 dark:text-gray-100 line-clamp-1 hover:text-[#002060] dark:hover:text-[#FFC000] transition-colors leading-snug" title="{{ $cleanName }}">
            {{ $cleanName }}
        </h3>
    </a>

    <!-- Prices Row -->
    <div class="flex items-baseline justify-start gap-2 mb-2">
        <span class="text-base md:text-lg font-black text-[#002060] dark:text-[#FFC000]">
            {{ core()->currency($flashPrice) }}
        </span>

        @if ($originalPrice > $flashPrice)
            <span class="text-xs text-gray-400 line-through font-semibold">
                {{ core()->currency($originalPrice) }}
            </span>
        @endif
    </div>

    <!-- Sales Progress Bar Component -->
    <x-flash_deal::shop.components.progress-bar 
        :sold-count="$soldCount"
        :stock="$stock"
    />

    <!-- Add to Cart Button -->
    <div class="mt-2 pt-1">
        <x-shop::products.card.action 
            :product="$productEntity"
            class="w-full bg-[#002060] hover:bg-[#001240] text-white font-extrabold py-2.5 px-4 rounded-2xl text-center text-xs transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg"
        >
            <span>أضف للسلة</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </x-shop::products.card.action>
    </div>
</div>
