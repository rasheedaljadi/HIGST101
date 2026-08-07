@props([
    'sectionTitle' => 'تنتهي العروض خلال',
    'endsAt' => null,
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
    class="w-full flex flex-col items-center pl-0 lg:pl-4"
>
    <!-- Header Title with Lightning Bolts -->
    <h2 class="text-2xl font-bold text-[#001A54] dark:text-white mb-6 flex items-center gap-2">
        <span class="text-[#FFC000]">⚡</span>
        <span>{{ $sectionTitle }}</span>
        <span class="text-[#FFC000]">⚡</span>
    </h2>

    <!-- 4 Countdown Box Widgets (Days, Hours, Minutes, Seconds) -->
    <div class="flex gap-3 md:gap-4 mb-6 text-center" dir="ltr">
        <!-- Days -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-16 h-20 md:w-20 md:h-24 flex items-center justify-center text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono" x-text="days">
                02
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-2 font-medium text-sm">يوم</span>
        </div>

        <!-- Hours -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-16 h-20 md:w-20 md:h-24 flex items-center justify-center text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono" x-text="hours">
                14
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-2 font-medium text-sm">ساعة</span>
        </div>

        <!-- Minutes -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-16 h-20 md:w-20 md:h-24 flex items-center justify-center text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono" x-text="minutes">
                36
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-2 font-medium text-sm">دقيقة</span>
        </div>

        <!-- Seconds -->
        <div class="flex flex-col items-center">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg w-16 h-20 md:w-20 md:h-24 flex items-center justify-center text-3xl md:text-4xl font-bold text-[#001A54] dark:text-[#FFC000] border border-gray-100 dark:border-gray-700 font-mono" x-text="seconds">
                58
            </div>
            <span class="text-gray-500 dark:text-gray-400 mt-2 font-medium text-sm">ثانية</span>
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
