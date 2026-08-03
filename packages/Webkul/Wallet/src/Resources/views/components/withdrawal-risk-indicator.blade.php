@props([
    'level' => 'Low',
    'colorClass' => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800',
    'factors' => [],
])

<div class="rounded-xl border p-5 shadow-sm transition-all duration-200 {{ $colorClass }}">
    <div class="flex items-center gap-3">
        <span class="text-xl">⚠️</span>
        <h3 class="text-lg font-bold">
            Risk Level: {{ $level }}
        </h3>
    </div>

    @if (! empty($factors))
        <ul class="mt-4 space-y-2.5 border-t border-black/10 pt-3 text-sm dark:border-white/10">
            @foreach ($factors as $factor)
                <li class="flex items-start gap-2">
                    <span class="mt-0.5 text-xs">⚠️</span>
                    <span>{{ $factor }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
