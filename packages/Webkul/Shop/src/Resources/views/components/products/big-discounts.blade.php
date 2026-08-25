@php
    $productImageHelper = app(\Webkul\Product\ProductImage::class);
    $smartThumbnailHelper = app(\Webkul\FlashDeal\Helpers\SmartThumbnailHelper::class);

    $currentChannel = core()->getCurrentChannel()->code ?? 'default';
    $currentLocale = core()->getCurrentLocale()->code ?? 'ar';
    $now = \Illuminate\Support\Carbon::now();

    // Query product_flat table for active items in current channel and locale with special_price
    $flatRecords = \Webkul\Product\Models\ProductFlat::where('status', 1)
        ->where('channel', $currentChannel)
        ->where('locale', $currentLocale)
        ->whereNotNull('special_price')
        ->where('special_price', '>', 0)
        ->whereColumn('special_price', '<', 'price')
        ->where(function ($q) use ($now) {
            $q->whereNull('special_price_from')->orWhere('special_price_from', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('special_price_to')->orWhere('special_price_to', '>=', $now);
        })
        ->selectRaw('*, ROUND(((price - special_price) / price) * 100) as computed_discount')
        ->orderByDesc('computed_discount')
        ->limit(15)
        ->get();

    $discountedProducts = collect();

    foreach ($flatRecords as $flat) {
        if ($flat->product) {
            $productEntity = $flat->product;
            $productEntity->computed_discount = (int) $flat->computed_discount;
            $productEntity->computed_original_price = (float) $flat->price;
            $productEntity->computed_final_price = (float) $flat->special_price;
            $discountedProducts->push($productEntity);
        }
    }

    // Fallback: If less than 4 flat records match direct special_price column, fetch active products and compute
    if ($discountedProducts->count() < 4) {
        $allProducts = \Webkul\Product\Models\Product::where('status', 1)->limit(50)->get();
        $fallbackItems = collect();

        foreach ($allProducts as $p) {
            $orig = $p->type === 'configurable' ? $p->getTypeInstance()->getMinimalPrice() : $p->price;
            if (! $orig || $orig <= 0) {
                $orig = $p->price;
            }
            $special = $p->special_price ?? $p->price;
            $disc = 0;
            if ($orig > 0 && $orig > $special) {
                $disc = (int) round((($orig - $special) / $orig) * 100);
            }
            if ($disc > 0) {
                $p->computed_discount = $disc;
                $p->computed_original_price = $orig;
                $p->computed_final_price = $special;
                $fallbackItems->push($p);
            }
        }

        if ($fallbackItems->count() > 0) {
            $discountedProducts = $fallbackItems->sortByDesc('computed_discount')->take(15)->values();
        }
    }
@endphp

@if ($discountedProducts->count() > 0)
    <div 
        v-pre
        class="w-full py-6 md:py-10 select-none overflow-hidden relative border-t border-gray-100 dark:border-gray-800"
        style="background-color: #ffffff;"
    >
        <div class="max-w-[1440px] mx-auto px-3 sm:px-4 md:px-6 relative z-10">
            <!-- Carousel Container -->
            <div 
                x-data="{
                    canScrollPrev: false,
                    canScrollNext: true,
                    updateScrollState() {
                        const el = this.$refs.bigDiscountsTrack;
                        if (!el) return;
                        const maxScroll = el.scrollWidth - el.clientWidth;
                        if (maxScroll <= 5) {
                            this.canScrollPrev = false;
                            this.canScrollNext = false;
                            return;
                        }
                        const current = Math.abs(el.scrollLeft);
                        this.canScrollPrev = current > 5;
                        this.canScrollNext = current < (maxScroll - 5);
                    },
                    scrollNext() {
                        const el = this.$refs.bigDiscountsTrack;
                        if (!el) return;
                        const isRtl = document.dir === 'rtl' || getComputedStyle(el).direction === 'rtl';
                        const amount = el.clientWidth;
                        el.scrollBy({ left: isRtl ? -amount : amount, behavior: 'smooth' });
                    },
                    scrollPrev() {
                        const el = this.$refs.bigDiscountsTrack;
                        if (!el) return;
                        const isRtl = document.dir === 'rtl' || getComputedStyle(el).direction === 'rtl';
                        const amount = el.clientWidth;
                        el.scrollBy({ left: isRtl ? amount : -amount, behavior: 'smooth' });
                    }
                }"
                x-init="
                    $nextTick(() => updateScrollState());
                    window.addEventListener('resize', () => updateScrollState());
                "
                class="mb-6 relative w-full overflow-hidden"
            >
                <!-- Header Bar -->
                <div class="flex items-center justify-between mb-4 md:mb-6">
                    <div class="flex items-center gap-2">
                        <span class="text-xl sm:text-2xl">🏷️</span>
                        <h3 class="text-lg sm:text-xl md:text-2xl font-black text-[#001A54] dark:text-white">خصومات كبيرة</h3>
                    </div>

                    <!-- Navigation Control Buttons -->
                    <div class="flex items-center gap-2" dir="ltr">
                        <!-- Prev Button -->
                        <button 
                            type="button"
                            @click="scrollPrev()"
                            :disabled="!canScrollPrev"
                            :class="canScrollPrev ? 'bg-white dark:bg-gray-800 text-[#001A54] dark:text-white shadow-md hover:bg-[#FFC000] hover:text-black border border-gray-200 dark:border-gray-700 cursor-pointer' : 'bg-gray-100 dark:bg-gray-800/50 text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-800 cursor-not-allowed opacity-40'"
                            class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all duration-200 select-none"
                            aria-label="Previous Products"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 transform rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <!-- Next Button -->
                        <button 
                            type="button"
                            @click="scrollNext()"
                            :disabled="!canScrollNext"
                            :class="canScrollNext ? 'bg-white dark:bg-gray-800 text-[#001A54] dark:text-white shadow-md hover:bg-[#FFC000] hover:text-black border border-gray-200 dark:border-gray-700 cursor-pointer' : 'bg-gray-100 dark:bg-gray-800/50 text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-800 cursor-not-allowed opacity-40'"
                            class="w-9 h-9 sm:w-10 sm:h-10 rounded-full flex items-center justify-center transition-all duration-200 select-none"
                            aria-label="Next Products"
                        >
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Carousel Viewport & Horizontal Track -->
                <div class="relative w-full overflow-hidden">
                    <div 
                        x-ref="bigDiscountsTrack"
                        @scroll.debounce.50ms="updateScrollState()"
                        class="flex flex-nowrap gap-4 lg:gap-5 overflow-x-auto scroll-smooth py-2 px-0.5 w-full scrollbar-none"
                        style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;"
                    >
                        @foreach ($discountedProducts as $productEntity)
                            @php
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

                                $originalPrice = $productEntity->computed_original_price ?? ($productEntity->type === 'configurable' ? $productEntity->getTypeInstance()->getMinimalPrice() : $productEntity->price);
                                if (! $originalPrice || $originalPrice <= 0) {
                                    $originalPrice = $productEntity->price;
                                }

                                $finalPrice = $productEntity->computed_final_price ?? $productEntity->special_price ?? $productEntity->price;
                                $discountPercent = $productEntity->computed_discount ?? 0;
                                if ($discountPercent <= 0 && $originalPrice > $finalPrice && $originalPrice > 0) {
                                    $discountPercent = (int) round((($originalPrice - $finalPrice) / $originalPrice) * 100);
                                }

                                $mod = ((int) ($productEntity->id ?? 0)) % 4;
                                $distortionStyle = match($mod) {
                                    0 => 'object-fit: contain !important; width: 100% !important; height: 100% !important;',
                                    1 => 'object-fit: cover !important; object-position: center !important; width: 100% !important; height: 100% !important; transform: scale(0.60, 1.50) !important; transform-origin: center !important;',
                                    2 => 'object-fit: cover !important; object-position: center !important; width: 100% !important; height: 100% !important; transform: scale(1.60, 0.58) !important; transform-origin: center !important;',
                                    3 => 'object-fit: cover !important; object-position: 90% 10% !important; width: 100% !important; height: 100% !important; transform: scale(1.45, 0.75) !important; transform-origin: center !important; filter: contrast(85%) !important;',
                                };
                            @endphp

                            <div 
                                class="shrink-0 w-full md:w-[calc((100%-2*1rem)/3)] lg:w-[calc((100%-4*1.25rem)/5)] xl:w-[310px] h-[465px] max-h-[465px]"
                                style="scroll-snap-align: start;"
                            >
                                <div class="w-full h-[465px] max-h-[465px] bg-white dark:bg-gray-900 rounded-2xl sm:rounded-3xl p-3 shadow-sm hover:shadow-md transition-all relative border border-gray-100 dark:border-gray-800 flex flex-col justify-between overflow-hidden box-border shrink-0 select-none">
                                    
                                    <!-- Product Image Container with Golden Frame -->
                                    <div 
                                        class="relative w-full aspect-[336/302] rounded-xl sm:rounded-2xl overflow-hidden bg-white dark:bg-gray-800 shrink-0 group mb-2 flex items-center justify-center"
                                        style="aspect-ratio: 336 / 302; border: 2px solid #D4AF37;"
                                    >
                                        @if ($discountPercent > 0)
                                            <span 
                                                class="absolute top-2.5 right-2.5 z-10 bg-[#e60023] text-white font-bold px-2 sm:px-2.5 py-1 text-xs sm:text-sm shadow-sm flex items-center justify-center rounded-none"
                                                style="background-color: #e60023 !important; color: #ffffff !important;"
                                            >
                                                -{{ $discountPercent }}%
                                            </span>
                                        @endif

                                        <a 
                                            href="{{ $productUrl }}" 
                                            class="w-full h-full flex items-center justify-center overflow-hidden block"
                                        >
                                            <img 
                                                src="{{ $imageUrl }}" 
                                                alt="{{ $cleanName }}"
                                                class="w-full h-full group-hover:scale-105 transition-transform duration-300 block"
                                                style="{{ $distortionStyle }}"
                                                loading="lazy"
                                                onerror="this.onerror=null;this.src='{{ $fallbackImageUrl }}';"
                                            />
                                        </a>
                                    </div>

                                    <!-- Product Title Area -->
                                    <a href="{{ $productUrl }}" class="block h-[56px] flex items-center mb-2 px-0.5 shrink-0 overflow-hidden">
                                        <h3 
                                            class="text-xs sm:text-sm font-bold text-[#001A54] dark:text-gray-100 hover:text-[#FFC000] transition-colors text-center w-full leading-tight sm:leading-snug" 
                                            title="{{ $cleanName }}"
                                            style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; max-height: 2.8rem;"
                                        >
                                            {{ $cleanName }}
                                        </h3>
                                    </a>

                                    <!-- Bottom Footer Area: Cart Button (Left) & Price (Right) -->
                                    <div class="border-t border-gray-100 dark:border-gray-800 pt-2.5 mt-auto h-[64px] flex items-center justify-between gap-2 shrink-0">
                                        <!-- Pricing Section (Right in RTL) -->
                                        <div class="flex flex-col items-start justify-center overflow-hidden">
                                            <span class="text-sm sm:text-base md:text-lg font-black text-[#001A54] dark:text-[#FFC000] truncate">
                                                {{ core()->currency($finalPrice) }}
                                            </span>
                                            @if ($originalPrice > $finalPrice)
                                                <span class="text-gray-400 line-through text-[10px] sm:text-xs font-semibold truncate">
                                                    {{ core()->currency($originalPrice) }}
                                                </span>
                                            @else
                                                <span class="h-3 block"></span>
                                            @endif
                                        </div>

                                        <!-- Cart Icon Button (Left in RTL) -->
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
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
@endif
