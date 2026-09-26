@props([
    'product',
])

@php
    $offerEndTime = null;
    $offerType = null;
    $offerName = null;
    $now = \Illuminate\Support\Carbon::now();

    // 1. Check active Catalog Rule for current channel
    try {
        $channelId = core()->getCurrentChannel()->id ?? 1;
        $todayStr = $now->format('Y-m-d');

        $rulePrice = $product->catalog_rule_prices
            ? $product->catalog_rule_prices
                ->where('channel_id', $channelId)
                ->where('rule_date', $todayStr)
                ->sortBy('price')
                ->first()
            : null;

        if ($rulePrice && ! empty($rulePrice->ends_till)) {
            $candidateEnd = \Illuminate\Support\Carbon::parse($rulePrice->ends_till);
            if ($candidateEnd->isFuture()) {
                $offerEndTime = $candidateEnd;
                $offerType = 'catalog_rule';

                if (! empty($rulePrice->catalog_rule_id)) {
                    $catalogRule = \Webkul\CatalogRule\Models\CatalogRule::find($rulePrice->catalog_rule_id);
                    if ($catalogRule && ! empty($catalogRule->name)) {
                        $offerName = $catalogRule->name;
                    }
                }
            }
        }
    } catch (\Throwable $e) {}

    // 2. Check active Special Price
    if (! $offerEndTime && (float) ($product->special_price ?? 0) > 0) {
        if (! empty($product->special_price_to)) {
            try {
                $candidateEnd = \Illuminate\Support\Carbon::parse($product->special_price_to.' 23:59:59');
                if ($candidateEnd->isFuture()) {
                    $offerEndTime = $candidateEnd;
                    $offerType = 'special_price';
                    $offerName = app()->getLocale() == 'ar' ? 'عرض خاص' : 'Special Offer';
                }
            } catch (\Throwable $e) {}
        }
    }

    // 3. Check active Flash Deal
    if (! $offerEndTime && class_exists('\Webkul\FlashDeal\Models\FlashDeal')) {
        try {
            $activeDeal = \Webkul\FlashDeal\Models\FlashDeal::where('status', 1)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now)
                ->whereHas('products', fn ($q) => $q->where('product_id', $product->id))
                ->first();

            if ($activeDeal && $activeDeal->ends_at) {
                $offerEndTime = \Illuminate\Support\Carbon::parse($activeDeal->ends_at);
                $offerType = 'flash_deal';
                $offerName = $activeDeal->name ?? $activeDeal->title ?? (app()->getLocale() == 'ar' ? 'عرض فلاش ديل' : 'Flash Deal');
            }
        } catch (\Throwable $e) {}
    }

    if (! $offerName) {
        $offerName = trans('shop::app.components.products.card.sale');
    }

    if ($offerEndTime) {
        $diffInSeconds = max(0, $now->diffInSeconds($offerEndTime, false));
        $days = floor($diffInSeconds / 86400);
        $hours = floor(($diffInSeconds % 86400) / 3600);
        $minutes = floor(($diffInSeconds % 3600) / 60);
        $seconds = $diffInSeconds % 60;

        $targetMs = $offerEndTime->getTimestamp() * 1000;
    }
@endphp

@if ($offerEndTime && $diffInSeconds > 0)
    <div 
        class="pdp-smart-countdown w-full my-3.5 p-3 sm:p-3.5 rounded-2xl bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-rose-500/10 dark:from-amber-950/40 dark:via-orange-950/40 dark:to-rose-950/40 border border-amber-300/70 dark:border-amber-700/50 shadow-xs flex flex-wrap items-center justify-between gap-3 select-none transition-all duration-300"
        data-end-timestamp="{{ $targetMs }}"
        data-product-id="{{ $product->id }}"
        data-offer-name="{{ $offerName }}"
    >
        <!-- Left / Label Area -->
        <div class="flex items-center gap-2.5">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
            </span>

            <div class="flex flex-col">
                <span class="text-xs sm:text-sm font-black text-rose-700 dark:text-rose-400 flex items-center gap-1 leading-tight">
                    ⚡ {{ $offerName }}
                </span>
                <span class="text-[11px] font-medium text-gray-600 dark:text-gray-300">
                    {{ app()->getLocale() == 'ar' ? 'ينتهي العرض الترويجي خلال:' : 'Special offer ends in:' }}
                </span>
            </div>
        </div>

        <!-- Right / Countdown Boxes -->
        <div class="flex items-center gap-1 sm:gap-1.5" dir="ltr">
            <!-- Days Box -->
            @if ($days > 0)
                <div class="flex flex-col items-center justify-center min-w-[38px] sm:min-w-[42px] px-1.5 py-1 rounded-xl bg-white dark:bg-gray-900 border border-amber-200/80 dark:border-gray-700 shadow-xs">
                    <span class="font-mono text-sm sm:text-base font-black text-amber-700 dark:text-amber-400 countdown-days leading-none">
                        {{ sprintf('%02d', $days) }}
                    </span>
                    <span class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400 font-semibold mt-0.5">
                        {{ app()->getLocale() == 'ar' ? 'يوم' : 'D' }}
                    </span>
                </div>

                <span class="text-amber-600 dark:text-amber-400 font-bold text-sm">:</span>
            @endif

            <!-- Hours Box -->
            <div class="flex flex-col items-center justify-center min-w-[38px] sm:min-w-[42px] px-1.5 py-1 rounded-xl bg-white dark:bg-gray-900 border border-amber-200/80 dark:border-gray-700 shadow-xs">
                <span class="font-mono text-sm sm:text-base font-black text-amber-700 dark:text-amber-400 countdown-hours leading-none">
                    {{ sprintf('%02d', $hours) }}
                </span>
                <span class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400 font-semibold mt-0.5">
                    {{ app()->getLocale() == 'ar' ? 'ساعة' : 'H' }}
                </span>
            </div>

            <span class="text-amber-600 dark:text-amber-400 font-bold text-sm">:</span>

            <!-- Minutes Box -->
            <div class="flex flex-col items-center justify-center min-w-[38px] sm:min-w-[42px] px-1.5 py-1 rounded-xl bg-white dark:bg-gray-900 border border-amber-200/80 dark:border-gray-700 shadow-xs">
                <span class="font-mono text-sm sm:text-base font-black text-amber-700 dark:text-amber-400 countdown-minutes leading-none">
                    {{ sprintf('%02d', $minutes) }}
                </span>
                <span class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400 font-semibold mt-0.5">
                    {{ app()->getLocale() == 'ar' ? 'دقيقة' : 'M' }}
                </span>
            </div>

            <span class="text-amber-600 dark:text-amber-400 font-bold text-sm">:</span>

            <!-- Seconds Box -->
            <div class="flex flex-col items-center justify-center min-w-[38px] sm:min-w-[42px] px-1.5 py-1 rounded-xl bg-white dark:bg-gray-900 border border-amber-200/80 dark:border-gray-700 shadow-xs">
                <span class="font-mono text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 countdown-seconds leading-none">
                    {{ sprintf('%02d', $seconds) }}
                </span>
                <span class="text-[9px] sm:text-[10px] text-gray-500 dark:text-gray-400 font-semibold mt-0.5">
                    {{ app()->getLocale() == 'ar' ? 'ثانية' : 'S' }}
                </span>
            </div>
        </div>
    </div>

    @pushonce('scripts')
        <script>
            (function() {
                function updatePdpSmartCountdowns() {
                    const now = Date.now();
                    document.querySelectorAll('.pdp-smart-countdown').forEach(function(el) {
                        const targetMs = parseInt(el.getAttribute('data-end-timestamp'), 10);
                        if (!targetMs) return;

                        const diff = targetMs - now;
                        if (diff <= 0) {
                            el.style.opacity = '0.6';
                            const offerName = el.getAttribute('data-offer-name') || 'العرض';
                            const label = el.querySelector('.text-rose-700, .dark\\:text-rose-400');
                            if (label) label.textContent = '⌛ انتهى ' + offerName;
                            return;
                        }

                        const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        const s = Math.floor((diff % (1000 * 60)) / 1000);

                        const daysEl = el.querySelector('.countdown-days');
                        if (daysEl) daysEl.textContent = String(d).padStart(2, '0');

                        const hoursEl = el.querySelector('.countdown-hours');
                        if (hoursEl) hoursEl.textContent = String(h).padStart(2, '0');

                        const minsEl = el.querySelector('.countdown-minutes');
                        if (minsEl) minsEl.textContent = String(m).padStart(2, '0');

                        const secsEl = el.querySelector('.countdown-seconds');
                        if (secsEl) secsEl.textContent = String(s).padStart(2, '0');
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        updatePdpSmartCountdowns();
                        setInterval(updatePdpSmartCountdowns, 1000);
                    });
                } else {
                    updatePdpSmartCountdowns();
                    setInterval(updatePdpSmartCountdowns, 1000);
                }
            })();
        </script>
    @endpushonce
@endif
