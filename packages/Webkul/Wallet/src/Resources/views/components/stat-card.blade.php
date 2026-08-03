@props([
    'title',
    'value',
    'icon' => null,
    'colorClass' => 'text-blue-600 dark:text-blue-400',
])

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-200 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ $title }}
        </span>

        @if ($icon)
            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                <span class="{{ $icon }} text-lg {{ $colorClass }}"></span>
            </div>
        @endif
    </div>

    <div class="mt-3">
        <p class="text-2xl font-extrabold tracking-tight {{ $colorClass }}">
            {{ $value }}
        </p>
    </div>
</div>
