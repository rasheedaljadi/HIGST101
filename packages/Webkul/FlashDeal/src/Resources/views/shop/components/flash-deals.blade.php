@props([
    'deal' => null,
])

@php
    $activeDeal = $deal ?? app(\Webkul\FlashDeal\Repositories\FlashDealRepository::class)->getActiveDeals()->first();
@endphp

@if ($activeDeal && $activeDeal->products->count() > 0)
    <div 
        v-pre
        class="w-full py-10 select-none overflow-hidden relative"
        style="background-color: #f8fafc; background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px;"
    >
        <div class="max-w-[1440px] mx-auto px-4 md:px-6 relative z-10">
            
            <!-- Hero Outer Container Card (Matches Proposal image_0.png) -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-6 md:p-8 shadow-sm border border-gray-100 dark:border-gray-800 mb-12">
                <section class="flex flex-col-reverse lg:flex-row gap-8 items-center justify-between">
                    
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

            <!-- Static 5-Product Grid Section (Forced 5 Columns Always) -->
            <section class="grid grid-cols-5 gap-4 md:gap-6 mb-12 flash-deals-5cols-grid">
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
                    class="inline-flex items-center gap-2 text-[#1f2937] dark:text-white font-bold hover:text-[#001A54] dark:hover:text-[#FFC000] transition-colors text-lg"
                >
                    <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/>
                    </svg>
                    <span>عرض جميع العروض</span>
                </a>
            </div>

        </div>
    </div>

    <!-- Scoped CSS forcing exactly 5 columns per row permanently -->
    <style>
        .flash-deals-5cols-grid {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 1.25rem !important;
        }
        @media (max-width: 1024px) {
            .flash-deals-5cols-grid {
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                gap: 0.75rem !important;
            }
        }
        @media (max-width: 640px) {
            .flash-deals-5cols-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 0.75rem !important;
            }
        }
    </style>
@endif
