@extends('shop::layouts.account')

@section('page_title')
    {{ __('wallet::app.shop.wallet.title') }}
@stop

@section('account-content')
<div class="flex flex-col gap-6">
    <p class="text-2xl font-bold text-gray-800 dark:text-white">
        {{ __('wallet::app.shop.wallet.title') }}
    </p>

    @if ($wallet)
        {{-- Balance Cards --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-lg">
                <p class="text-sm opacity-80">{{ __('wallet::app.shop.wallet.available-balance') }}</p>
                <p class="text-3xl font-bold mt-1">{{ core()->formatBasePrice($wallet->available_balance) }}</p>
            </div>
            @if ($wallet->held_balance > 0)
            <div class="rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 p-6 text-white shadow-lg">
                <p class="text-sm opacity-80">{{ __('wallet::app.shop.wallet.held-balance') }}</p>
                <p class="text-3xl font-bold mt-1">{{ core()->formatBasePrice($wallet->held_balance) }}</p>
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            @if (core()->getConfigData('sales.wallet.enable_topup'))
            <a href="{{ route('shop.customer.wallet.topup') }}" class="primary-button">
                {{ __('wallet::app.shop.wallet.topup') }}
            </a>
            @endif

            @if (core()->getConfigData('sales.wallet.enable_withdrawal'))
            <a href="{{ route('shop.customer.wallet.withdrawal.create') }}" class="secondary-button">
                {{ __('wallet::app.shop.wallet.withdraw') }}
            </a>
            @endif
        </div>

        {{-- Recent Transactions --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <p class="font-semibold text-gray-800 dark:text-white">{{ __('wallet::app.shop.wallet.transactions') }}</p>
                <a href="{{ route('shop.customer.wallet.transactions') }}" class="text-sm text-blue-600 hover:underline">
                    {{ __('admin::app.components.layouts.sidebar.view-all') }}
                </a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($wallet->transactions as $txn)
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $txn->type }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $txn->description }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $txn->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                    <p class="font-bold {{ $txn->isCredit() ? 'text-green-600' : 'text-red-500' }}">
                        {{ $txn->isCredit() ? '+' : '-' }}{{ core()->formatBasePrice($txn->amount) }}
                    </p>
                </div>
                @empty
                <p class="p-6 text-center text-gray-500">{{ __('wallet::app.shop.wallet.no-transactions') }}</p>
                @endforelse
            </div>
        </div>
    @else
        <p class="text-gray-500">{{ __('wallet::app.shop.wallet.no-transactions') }}</p>
    @endif
</div>
@stop
