@props([
    'sectionTitle' => 'عروض سريعة',
    'promotionalMessage' => '🔥 وفر حتى 60% على أحدث التشكيلات الحصرية!',
    'offerDescription' => 'عروض فائقة السرعة لفترة محدودة، اغتنم الفرصة الآن قبل نفاذ الكميات المتاحة!',
    'viewAllUrl' => route('shop.home.index'),
])

<div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-lg flex flex-col justify-between h-full relative overflow-hidden">
    <!-- Subtle top right glow line -->
    <div class="absolute top-0 right-0 left-0 h-1 bg-gradient-to-r from-[#002060] via-[#FFC000] to-[#002060]"></div>

    <!-- Section Header Row -->
    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
        <!-- Title & Icon -->
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-[#FFC000]/20 text-[#002060] dark:text-[#FFC000] flex items-center justify-center font-bold text-sm shrink-0">
                ⚡
            </div>
            <h3 class="text-xl md:text-2xl font-black text-[#002060] dark:text-white tracking-wide">
                {{ $sectionTitle }}
            </h3>
        </div>

        <!-- "View All Offers" Button -->
        <a 
            href="{{ $viewAllUrl }}" 
            class="inline-flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800 hover:bg-[#002060] hover:text-white text-[#002060] dark:text-[#FFC000] dark:hover:bg-[#FFC000] dark:hover:text-gray-950 font-extrabold text-xs md:text-sm px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700 transition-all duration-200 shadow-sm"
        >
            <span>عرض جميع العروض</span>
            <span class="text-sm font-bold">←</span>
        </a>
    </div>

    <!-- Offer Info Body (Promotional Message & Offer Description) -->
    <div class="space-y-3 my-auto py-2">
        @if ($promotionalMessage)
            <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-900/50 rounded-2xl p-3.5 flex items-start gap-3">
                <span class="text-xl shrink-0">🏷️</span>
                <p class="text-xs md:text-sm font-extrabold text-amber-950 dark:text-amber-200 leading-snug">
                    {{ $promotionalMessage }}
                </p>
            </div>
        @endif

        @if ($offerDescription)
            <p class="text-xs md:text-sm text-gray-600 dark:text-gray-300 font-medium leading-relaxed">
                {{ $offerDescription }}
            </p>
        @endif
    </div>

    <!-- Bottom Feature Badges -->
    <div class="grid grid-cols-2 gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-2 text-[11px] font-bold text-gray-700 dark:text-gray-300">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>توصيل سريع ومباشر</span>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-bold text-gray-700 dark:text-gray-300">
            <span class="w-2 h-2 rounded-full bg-[#FFC000]"></span>
            <span>منتجات أصلية 100%</span>
        </div>
    </div>
</div>
