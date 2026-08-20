<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.demands.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.demands.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.demands.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.demands.description') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if (bouncer()->hasPermission('dropshipping.procurement_v2.batch_create'))
                    <a href="{{ route('admin.procurement.batches.create') }}" class="primary-button flex items-center gap-2">
                        <span class="icon-plus text-lg"></span>
                        {{ trans('procurement::app.batches.create-batch') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Metrics Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.open-for-batching') }}</span>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ $counts['open_for_batching'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.batched') }}</span>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ $counts['batched'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.locally-covered') }}</span>
                <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-2">{{ $counts['locally_covered'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.demands.fulfilled') }}</span>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ $counts['fulfilled'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Datagrid --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <x-admin::datagrid :src="route('admin.procurement.demands.index')" />
        </div>
    </div>
</x-admin::layouts>
