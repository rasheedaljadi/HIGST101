<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.cost_variances.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('procurement::app.admin.menu.procurement-v2') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.cost_variances.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('procurement::app.cost_variances.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('procurement::app.cost_variances.description') }}
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <x-admin::datagrid :src="route('admin.procurement.cost_variances.index')" />
        </div>
    </div>
</x-admin::layouts>
