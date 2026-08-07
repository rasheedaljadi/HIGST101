@props([
    'soldCount' => 0,
    'stock' => 1,
])

@php
    $safeStock = max(1, (int) $stock);
    $safeSold = max(0, (int) $soldCount);
    $percentage = min(100, (int) round(($safeSold / $safeStock) * 100));
@endphp

<div class="w-full space-y-1.5 my-2">
    <!-- Progress Text Label -->
    <div class="flex items-center justify-between text-[10px] font-bold text-gray-500 dark:text-gray-400">
        <span>تم بيع <strong class="text-[#002060] dark:text-[#FFC000]">{{ $safeSold }}</strong> من {{ $safeStock }}</span>
        <span class="text-gray-400 font-mono">{{ $percentage }}%</span>
    </div>

    <!-- Progress Track & Bar -->
    <div class="relative w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
        <div 
            class="h-full bg-gradient-to-r from-[#FFC000] via-amber-400 to-amber-500 rounded-full transition-all duration-700 ease-out"
            style="width: {{ $percentage }}%;"
        ></div>
    </div>
</div>
