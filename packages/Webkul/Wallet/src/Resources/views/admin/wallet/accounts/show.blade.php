<x-admin::layouts>
    <x-slot:title>
        {{ __('wallet::app.admin.wallet.accounts.detail-title') ?? 'Wallet Account Details' }}: {{ $wallet->customer->name ?? '—' }}
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                {{ __('wallet::app.admin.wallet.accounts.detail-title') ?? 'Wallet Account Details' }}: {{ $wallet->customer->name ?? '—' }}
            </p>
            <a href="{{ route('admin.wallet.accounts.index') }}" class="secondary-button">
                ← {{ __('wallet::app.admin.wallet.back') ?? 'Back' }}
            </a>
        </div>

        {{-- Balance Summary --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ __('wallet::app.admin.wallet.accounts.available-balance') ?? 'Available Balance' }}</p>
                <p class="text-2xl font-bold text-green-600">{{ core()->formatBasePrice($wallet->available_balance) }}</p>
            </div>
            <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ __('wallet::app.admin.wallet.accounts.held-balance') ?? 'Held Balance' }}</p>
                <p class="text-2xl font-bold text-yellow-600">{{ core()->formatBasePrice($wallet->held_balance) }}</p>
            </div>
            <div class="rounded-xl border p-4 bg-white dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ __('wallet::app.admin.wallet.accounts.status') ?? 'Status' }}</p>
                <p class="text-2xl font-bold">{{ ucfirst($wallet->status) }}</p>
            </div>
        </div>

        {{-- Transactions DataGrid --}}
        <x-admin::datagrid :src="route('admin.wallet.accounts.show', $wallet->id)" />
    </div>
</x-admin::layouts>
