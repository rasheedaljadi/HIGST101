@props([
    'sectionTitle' => 'تنتهي العروض خلال',
    'endsAt' => null,
])

@php
    $carbonEndsAt = null;

    if ($endsAt) {
        try {
            $carbonEndsAt = \Illuminate\Support\Carbon::parse($endsAt);
        } catch (\Throwable $e) {}
    }

    if (! $carbonEndsAt) {
        $carbonEndsAt = \Illuminate\Support\Carbon::now()->addHours(24);
    }

    $now = \Illuminate\Support\Carbon::now();
    $diffInSeconds = max(0, $now->diffInSeconds($carbonEndsAt, false));

    $d = floor($diffInSeconds / (3600 * 24));
    $h = floor(($diffInSeconds % (3600 * 24)) / 3600);
    $m = floor(($diffInSeconds % 3600) / 60);
    $s = $diffInSeconds % 60;

    $days = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
    $hours = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
    $minutes = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    $seconds = str_pad((string) $s, 2, '0', STR_PAD_LEFT);

    $targetTimestamp = $carbonEndsAt->getTimestamp() * 1000;
@endphp

<div 
    class="w-full flex flex-col items-center pl-0 lg:pl-4 info-area-countdown"
    data-end-timestamp="{{ $targetTimestamp }}"
>
    <!-- Header Title with Lightning Bolts -->
    <h2 class="text-2xl font-bold text-[#001A54] dark:text-white mb-6 flex items-center gap-2">
        <span class="text-[#FFC000]">⚡</span>
        <span>{{ $sectionTitle }}</span>
        <span class="text-[#FFC000]">⚡</span>
    </h2>

    <!-- 4 Countdown Box Widgets (Days, Hours, Minutes, Seconds) -->
    <div class="flex gap-2 sm:gap-3 md:gap-4 mb-4 sm:mb-6 text-center justify-center w-full max-w-full" dir="ltr">
        <!-- Days -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-md sm:shadow-lg w-13 h-16 sm:w-16 sm:h-20 md:w-20 md:h-24 flex items-center justify-center text-xl sm:text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono info-days">
                {{ $days }}
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 font-medium text-xs sm:text-sm">يوم</span>
        </div>

        <!-- Hours -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-md sm:shadow-lg w-13 h-16 sm:w-16 sm:h-20 md:w-20 md:h-24 flex items-center justify-center text-xl sm:text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono info-hours">
                {{ $hours }}
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 font-medium text-xs sm:text-sm">ساعة</span>
        </div>

        <!-- Minutes -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-md sm:shadow-lg w-13 h-16 sm:w-16 sm:h-20 md:w-20 md:h-24 flex items-center justify-center text-xl sm:text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono info-minutes">
                {{ $minutes }}
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 font-medium text-xs sm:text-sm">دقيقة</span>
        </div>

        <!-- Seconds -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-md sm:shadow-lg w-13 h-16 sm:w-16 sm:h-20 md:w-20 md:h-24 flex items-center justify-center text-xl sm:text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono info-seconds">
                {{ $seconds }}
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-1 sm:mt-2 font-medium text-xs sm:text-sm">ثانية</span>
        </div>
    </div>

    <!-- Progress Track with Lightning Indicator on left -->
    <div class="w-full flex items-center gap-4 relative mt-2 max-w-xs md:max-w-sm">
        <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-visible">
            <div class="h-full bg-[#FFC000] rounded-full w-4/5"></div>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 -ml-2 w-8 h-8 bg-[#FFC000] rounded-full shadow-md flex items-center justify-center text-white text-sm font-bold">
                ⚡
            </div>
        </div>
    </div>
</div>

@pushonce('scripts')
    <script>
        (function() {
            function updateInfoAreaCountdowns() {
                const now = Date.now();
                document.querySelectorAll('.info-area-countdown').forEach(function (el) {
                    const endMs = parseInt(el.getAttribute('data-end-timestamp'), 10);
                    if (!endMs) return;

                    const daysEl = el.querySelector('.info-days');
                    const hoursEl = el.querySelector('.info-hours');
                    const minsEl = el.querySelector('.info-minutes');
                    const secsEl = el.querySelector('.info-seconds');

                    const diff = endMs - now;
                    if (diff <= 0) {
                        if (daysEl) daysEl.textContent = '00';
                        if (hoursEl) hoursEl.textContent = '00';
                        if (minsEl) minsEl.textContent = '00';
                        if (secsEl) secsEl.textContent = '00';
                        return;
                    }

                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);

                    if (daysEl) daysEl.textContent = String(d).padStart(2, '0');
                    if (hoursEl) hoursEl.textContent = String(h).padStart(2, '0');
                    if (minsEl) minsEl.textContent = String(m).padStart(2, '0');
                    if (secsEl) secsEl.textContent = String(s).padStart(2, '0');
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    updateInfoAreaCountdowns();
                    setInterval(updateInfoAreaCountdowns, 1000);
                });
            } else {
                updateInfoAreaCountdowns();
                setInterval(updateInfoAreaCountdowns, 1000);
            }
        })();
    </script>
@endpushonce
