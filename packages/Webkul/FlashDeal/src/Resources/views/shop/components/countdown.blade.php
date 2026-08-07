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
    class="flex items-center gap-1 text-gray-500 dark:text-gray-400 text-sm font-medium"
>
    <span dir="ltr" class="font-mono" x-text="hours + ':' + minutes + ':' + seconds">14:36:58</span>
    <span>🕒</span>
</div>
