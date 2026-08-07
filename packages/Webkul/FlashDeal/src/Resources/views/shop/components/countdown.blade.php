@props([
    'endTime' => null,
    'productId' => null,
])

@php
    $isoEndTime = $endTime ? \Illuminate\Support\Carbon::parse($endTime)->toISOString() : now()->addHours(12)->toISOString();
@endphp

<div 
    x-data="{
        endTimeMs: new Date('{{ $isoEndTime }}').getTime(),
        hours: '00',
        minutes: '00',
        seconds: '00',
        expired: false,
        timer: null,
        initTimer() {
            this.calculate();
            this.timer = setInterval(() => this.calculate(), 1000);
        },
        calculate() {
            const now = new Date().getTime();
            const distance = this.endTimeMs - now;

            if (distance <= 0) {
                this.hours = '00';
                this.minutes = '00';
                this.seconds = '00';
                this.expired = true;
                if (this.timer) clearInterval(this.timer);
                $dispatch('countdown-expired', { productId: '{{ $productId }}' });
                return;
            }

            const h = Math.floor(distance / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);

            this.hours = String(h).padStart(2, '0');
            this.minutes = String(m).padStart(2, '0');
            this.seconds = String(s).padStart(2, '0');
        }
    }"
    x-init="initTimer()"
    class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-gray-200/80 dark:border-gray-700 font-mono shadow-inner"
>
    <span class="text-amber-500 animate-pulse text-[11px]">🕒</span>
    <span x-text="hours + ':' + minutes + ':' + seconds">00:00:00</span>
</div>
