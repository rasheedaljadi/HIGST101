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
                    el.scrollBy({ left: -300, behavior: 'smooth' });
                }
            },
            scrollPrev() {
                const el = this.$refs.carouselTrack;
                if (el) {
                    el.scrollBy({ left: 300, behavior: 'smooth' });
                }
            }
        }"
        class="w-full py-10 select-none overflow-hidden relative"
        style="background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px;"
    >
        <div class="max-w-[1440px] mx-auto px-4 md:px-6 relative z-10">
            
            <!-- Hero Section (Timer 2/5 + Banner 3/5) -->
            <section class="flex flex-col-reverse lg:flex-row gap-8 mb-16 items-center justify-between">
                
                <!-- Timer Column (Left in LTR / Right in RTL ~40% width) -->
                <div class="w-full lg:w-2/5">
                    @include('flash_deal::shop.components.info-area', [
                        'sectionTitle' => 'تنتهي العروض خلال',
                        'endsAt' => $activeDeal->ends_at,
                    ])
                </div>

                <!-- Hero Banner Column (Right in LTR / Left in RTL ~60% width) -->
                <div class="w-full lg:w-3/5">
                    @include('flash_deal::shop.components.banner', [
                        'title' => $activeDeal->title ?? 'العروض السريعة',
                        'subtitle' => $activeDeal->subtitle ?? 'عروض محدودة لفترة قصيرة ..',
                        'description' => $activeDeal->description ?? 'اغتنم الفرصة قبل انتهاء الوقت!',
                        'bannerImage' => $activeDeal->banner_image,
                        'backgroundImage' => $activeDeal->background_image,
                        'accentColor' => $activeDeal->accent_color ?? '#fbbf24',
                        'secondaryColor' => $activeDeal->secondary_color ?? '#1e3a8a',
                    ])
                </div>

            </section>

            <!-- Products Slider Section -->
            <div class="relative group/carousel mb-12">
                
                <!-- Prev Button -->
                <button 
                    type="button"
                    @click="scrollPrev()"
                    class="absolute -right-3 md:-right-5 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-[#1e3a8a] hover:text-white transition-all duration-200 focus:outline-none"
                    aria-label="Previous"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Next Button -->
                <button 
                    type="button"
                    @click="scrollNext()"
                    class="absolute -left-3 md:-left-5 top-1/2 -translate-y-1/2 z-30 w-11 h-11 rounded-full bg-white dark:bg-gray-800 text-gray-800 dark:text-white shadow-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-[#1e3a8a] hover:text-white transition-all duration-200 focus:outline-none"
                    aria-label="Next"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- 5 Products Column Slider Track -->
                <div 
                    x-ref="carouselTrack"
                    class="flex gap-6 overflow-x-auto scroll-smooth py-4 px-1 no-scrollbar"
                    style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch;"
                >
                    @foreach ($activeDeal->products as $index => $dealProduct)
                        @if (! $dealProduct->product) @continue @endif

                        <div class="w-[calc(50%-0.75rem)] md:w-[calc(33.333%-1rem)] lg:w-[calc(25%-1.125rem)] xl:w-[calc(20%-1.2rem)] flex-shrink-0 snap-start flex">
                            @include('flash_deal::shop.components.product-card', [
                                'dealProduct' => $dealProduct,
                                'index' => $index,
                            ])
                        </div>
                    @endforeach
                </div>

            </div>

            <!-- Footer Action Button: View All Offers -->
            <div class="flex justify-center">
                <a 
                    href="{{ $activeDeal->view_all_url ?? route('shop.home.index') }}" 
                    class="inline-flex items-center gap-2 text-[#1f2937] dark:text-white font-bold hover:text-[#1e3a8a] dark:hover:text-[#fbbf24] transition-colors text-lg"
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
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
@endif
