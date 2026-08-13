<x-admin::layouts>
    <x-slot:title>
        {{ __('wallet::app.admin.wallet.accounts.title') ?? 'Wallet Accounts' }}
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        @include('wallet::admin.layouts.tabs')

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ __('wallet::app.admin.wallet.accounts.title') ?? 'Wallet Accounts' }}
            </p>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.accounts.index')" />
    </div>
</x-admin::layouts>
