<x-admin::layouts>
    <x-slot:title>
        {{ __('wallet::app.admin.wallet.withdrawals.title') ?? 'Wallet Withdrawals' }}
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ __('wallet::app.admin.wallet.withdrawals.title') ?? 'Wallet Withdrawals' }}
            </p>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.withdrawals.index')" />
    </div>
</x-admin::layouts>
