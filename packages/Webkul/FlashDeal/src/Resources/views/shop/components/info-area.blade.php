@props([
    'sectionTitle' => 'تنتهي العروض خلال',
    'endsAt' => null,
    'promotionalMessage' => null,
    'offerDescription' => null,
    'viewAllUrl' => route('shop.home.index'),
])

@php
    $isoEndsAt = $endsAt ? \Illuminate\Support\Carbon::parse($endsAt)->toISOString() : now()->addHours(24)->toISOString();
@endphp

<div 
    x-data="{
        endsAtMs: new Date('{{ $isoEndsAt }}').getTime(),
        days: '00',
        hours: '00',
        minutes: '00',
        seconds: '00',
        timer: null,
        initTimer() {
            this.update();
            this.timer = setInterval(() => this.update(), 1000);
        },
        update() {
            const now = new Date().getTime();
            const distance = this.endsAtMs - now;

            if (distance <= 0) {
                this.days = '00';
                this.hours = '00';
                this.minutes = '00';
                this.seconds = '00';
                if (this.timer) clearInterval(this.timer);
                return;
            }

            const d = Math.floor(distance / (1000 * 60 * 60 * 24));
            const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);

            this.days = String(d).padStart(2, '0');
            this.hours = String(h).padStart(2, '0');
            this.minutes = String(m).padStart(2, '0');
            this.seconds = String(s).padStart(2, '0');
        }
    }"
    x-init="initTimer()"
    class="w-full bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-[2.2rem] p-6 shadow-xl flex flex-col justify-between min-h-[170px] relative overflow-hidden"
>
    <!-- Header Title with Lightning Bolts -->
    <div class="flex items-center justify-center gap-2 text-base md:text-lg font-black text-[#002060] dark:text-white mb-3">
        <span class="text-[#FFC000] text-xl animate-pulse">⚡</span>
        <span>{{ $sectionTitle }}</span>
        <span class="text-[#FFC000] text-xl animate-pulse">⚡</span>
    </div>

    <!-- 4 Countdown Box Widgets (Days, Hours, Minutes, Seconds) -->
    <div class="grid grid-cols-4 gap-2.5 md:gap-3 text-center my-auto">
        <!-- Seconds (ثانية) -->
        <div class="bg-gray-50/80 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/80 rounded-2xl p-2.5 md:p-3 shadow-sm transition-transform hover:scale-105">
            <span class="text-2xl md:text-3xl font-black text-[#002060] dark:text-[#FFC000] block leading-none font-mono" x-text="seconds">00</span>
            <span class="text-[11px] font-extrabold text-gray-500 dark:text-gray-400 block mt-1.5">ثانية</span>
        </div>

        <!-- Minutes (دقيقة) -->
        <div class="bg-gray-50/80 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/80 rounded-2xl p-2.5 md:p-3 shadow-sm transition-transform hover:scale-105">
            <span class="text-2xl md:text-3xl font-black text-[#002060] dark:text-[#FFC000] block leading-none font-mono" x-text="minutes">00</span>
            <span class="text-[11px] font-extrabold text-gray-500 dark:text-gray-400 block mt-1.5">دقيقة</span>
        </div>

        <!-- Hours (ساعة) -->
        <div class="bg-gray-50/80 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/80 rounded-2xl p-2.5 md:p-3 shadow-sm transition-transform hover:scale-105">
            <span class="text-2xl md:text-3xl font-black text-[#002060] dark:text-[#FFC000] block leading-none font-mono" x-text="hours">00</span>
            <span class="text-[11px] font-extrabold text-gray-500 dark:text-gray-400 block mt-1.5">ساعة</span>
        </div>

        <!-- Days (يوم) -->
        <div class="bg-gray-50/80 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/80 rounded-2xl p-2.5 md:p-3 shadow-sm transition-transform hover:scale-105">
            <span class="text-2xl md:text-3xl font-black text-[#002060] dark:text-[#FFC000] block leading-none font-mono" x-text="days">00</span>
            <span class="text-[11px] font-extrabold text-gray-500 dark:text-gray-400 block mt-1.5">يوم</span>
        </div>
    </div>

    <!-- Bottom Decorative Yellow Progress Line with Flash Handle -->
    <div class="relative w-full h-2.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-visible flex items-center mt-3">
        <div class="h-full bg-[#FFC000] rounded-full w-5/6"></div>
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full bg-[#FFC000] border-2 border-white dark:border-gray-900 shadow-md flex items-center justify-center text-gray-950 text-[10px] font-black">
            ⚡
        </div>
    </div>
</div>
