@props([
    'deal' => null,
])

@php
    $activeDeal = $deal ?? app(\Webkul\FlashDeal\Repositories\FlashDealRepository::class)->getActiveDeals()->first();
@endphp

@if ($activeDeal && $activeDeal->products->count() > 0)
    <div 
        v-pre
        class="w-full py-6 md:py-10 select-none overflow-hidden relative"
        style="background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px;"
    >
        <div class="max-w-[1440px] mx-auto px-3 sm:px-4 md:px-6 relative z-10">
            
            <!-- Hero Outer Container Card -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl md:rounded-[2.5rem] p-4 sm:p-6 md:p-8 shadow-sm border border-gray-100 dark:border-gray-800 mb-6 md:mb-12">
                <section class="flex flex-col-reverse lg:flex-row gap-6 md:gap-8 items-center justify-between">
                    
                    <!-- Timer Column -->
                    <div class="w-full lg:w-2/5">
                        @include('flash_deal::shop.components.info-area', [
                            'sectionTitle' => 'تنتهي العروض خلال',
                            'endsAt' => $activeDeal->ends_at,
                        ])
                    </div>

                    <!-- Hero Banner Column -->
                    <div class="w-full lg:w-3/5">
                        @include('flash_deal::shop.components.banner', [
                            'title' => $activeDeal->title ?? 'عرض الصيف',
                            'subtitle' => $activeDeal->subtitle ?? 'عروض محدودة لفترة قصيرة ..',
                            'description' => $activeDeal->description ?? 'اغتنم الفرصة قبل انتهاء الوقت!',
                            'bannerImage' => $activeDeal->banner_image,
                            'backgroundImage' => $activeDeal->background_image,
                            'accentColor' => $activeDeal->accent_color ?? '#FFC000',
                            'secondaryColor' => $activeDeal->secondary_color ?? '#001A54',
                        ])
                    </div>

                </section>
            </div>

            <!-- Responsive Horizontal Product Carousel Section -->
            <div 
                x-data="{
                    canScrollPrev: false,
                    canScrollNext: true,
                    updateScrollState() {
                        const el = this.$refs.carouselTrack;
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
                        const el = this.$refs.carouselTrack;
                        if (!el) return;
                        const isRtl = document.dir === 'rtl' || getComputedStyle(el).direction === 'rtl';
                        const amount = el.clientWidth;
                        el.scrollBy({ left: isRtl ? -amount : amount, behavior: 'smooth' });
                    },
                    scrollPrev() {
                        const el = this.$refs.carouselTrack;
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
                class="mb-8 md:mb-12 relative w-full overflow-hidden"
            >
                <!-- Header Bar with Title & Navigation Controls -->
                <div class="flex items-center justify-between mb-4 md:mb-6">
                    <div class="flex items-center gap-2">
                        <span class="text-xl sm:text-2xl">🔥</span>
                        <h3 class="text-lg sm:text-xl md:text-2xl font-black text-[#001A54] dark:text-white">أحدث العروض</h3>
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

                <!-- Carousel Viewport & Single Horizontal Track -->
                <div class="relative w-full overflow-hidden">
                    <div 
                        x-ref="carouselTrack"
                        @scroll.debounce.50ms="updateScrollState()"
                        class="flex flex-nowrap gap-4 lg:gap-5 overflow-x-auto scroll-smooth py-2 px-0.5 w-full scrollbar-none"
                        style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none;"
                    >
                        @foreach ($activeDeal->products as $index => $dealProduct)
                            @if (! $dealProduct->product) @continue @endif

                            <div 
                                class="shrink-0 w-full md:w-[calc((100%-2*1rem)/3)] lg:w-[calc((100%-4*1.25rem)/5)] xl:w-[310px] h-[505px] max-h-[505px]"
                                style="scroll-snap-align: start;"
                            >
                                @include('flash_deal::shop.components.product-card', [
                                    'dealProduct' => $dealProduct,
                                    'index' => $index,
                                ])
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Footer Action Button: View All Offers -->
            <div class="flex justify-center">
                <a 
                    href="{{ $activeDeal->view_all_url ?? route('shop.home.index') }}" 
                    class="inline-flex items-center gap-2 text-[#1f2937] dark:text-white font-bold hover:text-[#001A54] dark:hover:text-[#FFC000] transition-colors text-base md:text-lg"
                >
                    <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/>
                    </svg>
                    <span>عرض جميع العروض</span>
                </a>
            </div>

        </div>
    </div>

    <style>
        [x-ref="carouselTrack"]::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
    </style>
@endif
