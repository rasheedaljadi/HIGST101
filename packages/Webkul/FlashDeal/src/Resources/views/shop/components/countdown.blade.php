@props([
    'endTime' => null,
    'productId' => null,
])

@php
    $carbonEndTime = null;

    if ($endTime) {
        try {
            $carbonEndTime = \Illuminate\Support\Carbon::parse($endTime);
        } catch (\Throwable $e) {}
    }

    if (! $carbonEndTime) {
        $carbonEndTime = \Illuminate\Support\Carbon::now()->addHours(12);
    }

    $now = \Illuminate\Support\Carbon::now();
    $diffInSeconds = max(0, $now->diffInSeconds($carbonEndTime, false));

    $hours = str_pad((string) floor($diffInSeconds / 3600), 2, '0', STR_PAD_LEFT);
    $minutes = str_pad((string) floor(($diffInSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
    $seconds = str_pad((string) ($diffInSeconds % 60), 2, '0', STR_PAD_LEFT);

    $formattedInitialTime = "{$hours}:{$minutes}:{$seconds}";
    $targetTimestamp = $carbonEndTime->getTimestamp() * 1000;
@endphp

<div 
    class="flex items-center gap-1 text-gray-500 dark:text-gray-400 text-sm font-medium flash-deal-countdown"
    data-end-timestamp="{{ $targetTimestamp }}"
    data-product-id="{{ $productId }}"
>
    <span dir="ltr" class="font-mono countdown-timer-text">{{ $formattedInitialTime }}</span>
    <span>🕒</span>
</div>

@pushonce('scripts')
    <script>
        (function() {
            function updateFlashDealCountdowns() {
                const now = Date.now();
                document.querySelectorAll('.flash-deal-countdown').forEach(function (el) {
                    const endMs = parseInt(el.getAttribute('data-end-timestamp'), 10);
                    if (!endMs) return;

                    const timerTextEl = el.querySelector('.countdown-timer-text');
                    if (!timerTextEl) return;

                    const diff = endMs - now;
                    if (diff <= 0) {
                        timerTextEl.textContent = '00:00:00';
                        return;
                    }

                    const h = Math.floor(diff / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);

                    const hh = String(h).padStart(2, '0');
                    const mm = String(m).padStart(2, '0');
                    const ss = String(s).padStart(2, '0');

                    timerTextEl.textContent = `${hh}:${mm}:${ss}`;
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    updateFlashDealCountdowns();
                    setInterval(updateFlashDealCountdowns, 1000);
                });
            } else {
                updateFlashDealCountdowns();
                setInterval(updateFlashDealCountdowns, 1000);
            }
        })();
    </script>
@endpushonce
