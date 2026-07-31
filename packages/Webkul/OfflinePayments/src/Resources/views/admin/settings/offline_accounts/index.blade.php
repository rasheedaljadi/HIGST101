<x-admin::layouts>
    <x-slot:title>
        @lang('offline_payments::app.admin.menu.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('offline_payments::app.admin.menu.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('settings.offline_payment_accounts.create'))
                <a
                    href="{{ route('admin.settings.offline_accounts.create') }}"
                    class="primary-button"
                >
                    @lang('offline_payments::app.admin.actions.create')
                </a>
            @endif
        </div>
    </div>

    <x-admin::datagrid :src="route('admin.settings.offline_accounts.index')" />
</x-admin::layouts>
