@props([
    'events' => [],
])

<div class="relative border-l-2 border-gray-200 dark:border-gray-800 ml-4 space-y-6">
    @foreach ($events as $event)
        <div class="relative pl-6">
            {{-- Dot / Icon Node on Line --}}
            <div class="absolute -left-[17px] top-1 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-100 dark:border-gray-900 dark:bg-gray-800 shadow-sm">
                <span class="{{ $event['icon'] ?? 'icon-dot' }} text-sm {{ $event['color'] ?? 'text-gray-600 dark:text-gray-300' }}"></span>
            </div>

            {{-- Narrative Event Content Box --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-800 dark:text-white">
                            {{ $event['type'] }}
                        </span>
                        <span class="text-xs text-gray-400">
                            • {{ $event['desc'] }}
                        </span>
                    </div>

                    <span class="text-sm font-extrabold tracking-tight {{ $event['color'] }}">
                        {{ $event['amount'] }}
                    </span>
                </div>

                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $event['date'] }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
