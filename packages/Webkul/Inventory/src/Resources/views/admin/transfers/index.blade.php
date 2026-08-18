<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.transfers.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('inventory::app.admin.menu.inventory') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.transfers.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.transfers.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('inventory::app.admin.transfers.description') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.transfers.create') }}" class="primary-button flex items-center gap-2">
                    <span class="icon-plus text-xl"></span>
                    {{ trans('inventory::app.admin.transfers.create-title') }}
                </a>
            </div>
        </div>

        {{-- Transfers DataGrid --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm">
            <x-admin::datagrid :src="route('admin.inventory.transfers.index')" />
        </div>
    </div>
</x-admin::layouts>
