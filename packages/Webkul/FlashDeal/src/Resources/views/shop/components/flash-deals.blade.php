@props([
    'deal' => null,
])

@php
    $activeDeal = $deal ?? app(\Webkul\FlashDeal\Repositories\FlashDealRepository::class)->getActiveDeals()->first();
@endphp

@if ($activeDeal && $activeDeal->products->count() > 0)
    <div 
        x-data="{
            scrollNext() {
                const el = this.$refs.carouselTrack;
                if (el) {
                    el.scrollBy({ left: -320, behavior: 'smooth' });
                }
            },
            scrollPrev() {
                const el = this.$refs.carouselTrack;
                if (el) {
                    el.scrollBy({ left: 320, behavior: 'smooth' });
                }
            }
        }"
        class="w-full my-8 py-8 bg-gradient-to-b from-gray-50/60 via-white to-gray-50/40 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 border-y border-gray-100 dark:border-gray-800 select-none overflow-hidden relative"
    >
        <!-- Background subtle decorative pattern -->
        <div class="absolute inset-0 bg-[radial-gradient(#002060_1px,transparent_1px)] [background-size:24px_24px] opacity-[0.03] dark:opacity-[0.07] pointer-events-none"></div>

        <div class="max-w-[1440px] mx-auto px-4 md:px-8 relative z-10">
            
            <!-- Top Section Row: Promotional Banner (60%) + Offer Information Area (40%) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8 items-stretch">
                
                <!-- Promotional Banner (Top Left ~60%) -->
                <div class="lg:col-span-7 flex">
                    <x-flash_deal::shop.components.banner 
                        :title="$activeDeal->title ?? 'عروض خاطفة مميزة'"
                        :subtitle="$activeDeal->subtitle ?? 'خصومات استثنائية لفترة محدودة جداً'"
                        :description="$activeDeal->description ?? 'اكتشف أرقى المنتجات بأسعار حصرية وحسومات فائقة قبل نفاد الكمية'"
                        :banner-image="$activeDeal->banner_image"
                        :background-image="$activeDeal->background_image"
                        :accent-color="$activeDeal->accent_color ?? '#FFC000'"
                        :secondary-color="$activeDeal->secondary_color ?? '#002060'"
                    />
                </div>

                <!-- Offer Information Area (Top Right ~40%) -->
                <div class="lg:col-span-5 flex">
                    <x-flash_deal::shop.components.info-area 
                        :section-title="$activeDeal->title ?? 'العروض السريعة'"
                        :promotional-message="$activeDeal->promotional_message ?? '🔥 وفر حتى 60% على تشكيلة المنتجات الحصرية'"
                        :offer-description="$activeDeal->offer_description ?? 'سارع بالشراء قبل انتهاء الكمية المخصصة لهذه العروض الحصرية!'"
                        :view-all-url="$activeDeal->view_all_url ?? route('shop.home.index')"
                    />
                </div>

            </div>

            <!-- Products Slider / Grid Section Header & Navigation Controls -->
            <div class="relative group/carousel">
                
                <!-- Navigation Prev Button (Right side in RTL) -->
                <button 
                    type="button"
                    @click="scrollPrev()"
                    class="absolute -right-3 md:-right-5 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-2xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-[#002060] hover:text-white dark:hover:bg-[#FFC000] dark:hover:text-gray-950 transition-all duration-200 focus:outline-none"
                    aria-label="Previous Products"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Navigation Next Button (Left side in RTL) -->
                <button 
                    type="button"
                    @click="scrollNext()"
                    class="absolute -left-3 md:-left-5 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-2xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-[#002060] hover:text-white dark:hover:bg-[#FFC000] dark:hover:text-gray-950 transition-all duration-200 focus:outline-none"
                    aria-label="Next Products"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- Products Carousel Track (Desktop: 5 items visible per row) -->
                <div 
                    x-ref="carouselTrack"
                    class="flex gap-4 overflow-x-auto scroll-smooth py-4 px-1 no-scrollbar"
                    style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;"
                >
                    @foreach ($activeDeal->products as $dealProduct)
                        @if (! $dealProduct->product) @continue @endif

                        <!-- Product Card Item: Desktop 5 cols (20%), Laptop 4 cols (25%), Tablet 3 cols (33.33%), Mobile 2 cols (50%) -->
                        <div class="w-[calc(50%-0.5rem)] md:w-[calc(33.333%-0.75rem)] lg:w-[calc(25%-0.75rem)] xl:w-[calc(20%-0.8rem)] flex-shrink-0 snap-start flex">
                            <x-flash_deal::shop.components.product-card 
                                :deal-product="$dealProduct"
                            />
                        </div>
                    @endforeach
                </div>

            </div>

        </div>
    </div>

    <!-- Custom CSS for Hiding Scrollbars -->
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
@endif
