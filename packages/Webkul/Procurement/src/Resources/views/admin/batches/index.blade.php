<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.batches.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.batches.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.batches.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.batches.description') }}
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
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.batches.total-batches') }}</span>
                <div class="text-2xl font-bold text-gray-800 dark:text-white mt-2">{{ $counts['all'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.states.ready_for_review') }}</span>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ $counts['ready_for_review'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.states.approved') }}</span>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ $counts['approved'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.states.awaiting_manual_payment') }}</span>
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2">{{ $counts['awaiting_manual_payment'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ trans('procurement::app.states.cost_variance_review') }}</span>
                <div class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-2">{{ $counts['cost_variance_review'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Datagrid --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <x-admin::datagrid :src="route('admin.procurement.batches.index')" />
        </div>
    </div>
</x-admin::layouts>
