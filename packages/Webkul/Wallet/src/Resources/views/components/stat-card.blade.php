@props([
    'title',
    'subtitle' => '',
    'value',
    'icon' => null,
    'colorClass' => 'text-blue-600 dark:text-blue-400',
    'bgCircleClass' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400',
    'waveColor' => '#3b82f6',
])

<div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
    <div>
        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-base font-bold text-gray-800 dark:text-white">
                    {{ $title }}
                </span>
                @if ($subtitle)
                    <span class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        {{ $subtitle }}
                    </span>
                @endif
            </div>

            @if ($icon)
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $bgCircleClass }}">
                    @if ($icon === 'icon-dollar' || $icon === 'wallet-blue')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    @elseif ($icon === 'icon-wallet' || $icon === 'wallet-green')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                    @elseif ($icon === 'icon-lock' || $icon === 'lock-orange')
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    @else
                        <span class="{{ $icon }} text-xl"></span>
                    @endif
                </div>
            @endif
        </div>

        <div class="mt-6 mb-1">
            <p class="text-3xl font-extrabold tracking-tight font-mono {{ $colorClass }}">
                {{ $value }}
            </p>
        </div>
    </div>

    {{-- Bottom Decorative Soft Wave --}}
    <div class="mt-4 -mx-6 -mb-6 h-12 overflow-hidden opacity-30">
        <svg class="w-full h-full" viewBox="0 0 500 150" preserveAspectRatio="none">
            <path d="M0.00,49.98 C150.00,150.00 349.20,-49.98 500.00,49.98 L500.00,150.00 L0.00,150.00 Z" fill="{{ $waveColor }}"></path>
        </svg>
    </div>
</div>
