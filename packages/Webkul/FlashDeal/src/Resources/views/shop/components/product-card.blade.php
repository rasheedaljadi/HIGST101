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
    class="w-full bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-md hover:shadow-xl transition-all relative border {{ $isBestSeller ? 'border-2 border-[#fbbf24] transform lg:-translate-y-2 shadow-yellow-100 dark:shadow-none' : 'border-transparent' }} flex flex-col justify-between"
>
    <!-- Featured "Best Seller" Badge -->
    @if ($badgeText || $isBestSeller)
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-[#1e3a8a] text-[#fbbf24] font-bold px-4 py-1 rounded-full text-xs flex items-center gap-1.5 whitespace-nowrap shadow-md z-20">
            <span>{{ $badgeText ?? 'الأكثر مبيعاً' }}</span>
            <span>🔥</span>
        </div>
    @endif

    <!-- Top Card Bar: Discount & Countdown -->
    <div class="flex justify-between items-center mb-4 {{ ($badgeText || $isBestSeller) ? 'mt-2' : '' }}">
        @if ($discountPercent > 0)
            <span class="bg-[#fbbf24] text-[#1e3a8a] font-bold px-3 py-1 rounded-full text-sm shadow-sm">-{{ $discountPercent }}%</span>
        @else
            <div></div>
        @endif

        @include('flash_deal::shop.components.countdown', [
            'endTime' => $endTime,
            'productId' => $productEntity->id,
        ])
    </div>

    <!-- Product Image -->
    <a href="{{ $productUrl }}" class="h-48 mb-4 flex items-center justify-center p-2 group">
        <img 
            src="{{ $imageUrl }}" 
            alt="{{ $cleanName }}"
            class="max-h-full max-w-full object-contain drop-shadow-lg group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
        />
    </a>

    <!-- Product Content & Pricing -->
    <div class="text-center">
        <a href="{{ $productUrl }}" class="block mb-1">
            <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 mb-1 line-clamp-1 hover:text-[#1e3a8a] dark:hover:text-[#fbbf24] transition-colors" title="{{ $cleanName }}">
                {{ $cleanName }}
            </h3>
        </a>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-3 line-clamp-1">
            شحن سريع - ضمان الجودة العالية
        </p>

        <div class="flex items-center justify-center gap-2 mb-4" dir="ltr">
            <span class="text-2xl font-bold text-[#1e3a8a] dark:text-[#fbbf24]">
                {{ core()->currency($flashPrice) }}
            </span>
            @if ($originalPrice > $flashPrice)
                <span class="text-gray-400 line-through text-sm">
                    {{ core()->currency($originalPrice) }}
                </span>
            @endif
        </div>

        <!-- Add to Cart Button -->
        <a 
            href="{{ $productUrl }}" 
            class="w-full {{ $isBestSeller ? 'bg-[#fbbf24] hover:bg-yellow-500 text-[#1e3a8a] shadow-lg shadow-yellow-200' : 'bg-[#1e3a8a] hover:bg-blue-800 text-white' }} font-bold py-3 rounded-xl transition-colors flex items-center justify-center gap-2 shadow text-sm"
        >
            <span>أضف للسلة</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </a>
    </div>
</div>
