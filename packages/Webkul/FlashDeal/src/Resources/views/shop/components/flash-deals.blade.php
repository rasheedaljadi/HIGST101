@props([
    'deal' => null,
])

@php
    $activeDeal = $deal ?? app(\Webkul\FlashDeal\Repositories\FlashDealRepository::class)->getActiveDeals()->first();
@endphp

@if ($activeDeal && $activeDeal->products->count() > 0)
    <div 
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

            <!-- Static 5-Product Grid Section (No Scrollbar / No Carousel Slider) -->
            <section class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-12">
                @foreach ($activeDeal->products->take(5) as $index => $dealProduct)
                    @if (! $dealProduct->product) @continue @endif

                    @include('flash_deal::shop.components.product-card', [
                        'dealProduct' => $dealProduct,
                        'index' => $index,
                    ])
                @endforeach
            </section>

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
@endif
