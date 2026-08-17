<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.audit-logs.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.dashboard.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.menu.delivery-management') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.audit-logs.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.audit-logs.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('delivery::app.admin.audit-logs.description') }}
                </p>
            </div>
        </div>

        {{-- Real Bagisto DataGrid --}}
        <x-admin::datagrid :src="route('admin.delivery.audit_logs.index')" />
    </div>
</x-admin::layouts>
