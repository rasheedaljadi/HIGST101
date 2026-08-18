<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.products.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('inventory::app.admin.menu.inventory') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.products.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.products.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('inventory::app.admin.products.description') }}
                </p>
            </div>
        </div>

        {{-- Isolated Virtual Projection Callout --}}
        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300 shrink-0">
                <span class="icon-information text-xl"></span>
            </div>
            <p class="text-xs text-amber-900 dark:text-amber-200">
                {{ trans('inventory::app.admin.products.external-projection-notice') }}
            </p>
        </div>

        {{-- Products DataGrid --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm">
            <x-admin::datagrid :src="route('admin.inventory.products.index')" />
        </div>
    </div>
</x-admin::layouts>
