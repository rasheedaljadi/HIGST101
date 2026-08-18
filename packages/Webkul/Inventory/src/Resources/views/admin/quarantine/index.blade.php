<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.quarantine.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('inventory::app.admin.menu.inventory') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.quarantine.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.quarantine.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('inventory::app.admin.quarantine.description') }}
                </p>
            </div>
        </div>

        {{-- Policy Notice --}}
        <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-700 dark:text-rose-300 shrink-0">
                <span class="icon-warning text-xl"></span>
            </div>
            <p class="text-xs text-rose-900 dark:text-rose-200">
                لا يُسمح بإعادة المنتج من الحجر إلى المخزون القابل للبيع إلا بعد فحص الجودة المعتمد وبصلاحية المشرف (Supervisor) أو المدير (Administrator). كل إجراء يسجل حركة مخزنية رسمية.
            </p>
        </div>

        {{-- Quarantine DataGrid --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm">
            <x-admin::datagrid :src="route('admin.inventory.quarantine.index')" />
        </div>
    </div>
</x-admin::layouts>
