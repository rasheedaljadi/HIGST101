@extends('shop::layouts.account')

@section('page_title')
    {{ __('wallet::app.shop.wallet.transactions') }}
@stop

@section('account-content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
            {{ __('wallet::app.shop.wallet.transactions') }}
        </p>
        <a href="{{ route('shop.customer.wallet.index') }}" class="secondary-button text-sm">
            ← {{ __('wallet::app.admin.wallet.back') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($transactions as $txn)
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $txn->type_label }}</p>
                <p class="text-xs text-gray-500">{{ $txn->description }}</p>
                <p class="text-xs text-gray-400">{{ $txn->created_at->format('Y-m-d H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold {{ $txn->isCredit() ? 'text-green-600' : 'text-red-500' }}">
                    {{ $txn->isCredit() ? '+' : '-' }}{{ core()->formatBasePrice($txn->amount) }}
                </p>
                <p class="text-xs text-gray-400">{{ __('wallet::app.admin.wallet.transactions.balance-after') }}: {{ core()->formatBasePrice($txn->running_balance) }}</p>
            </div>
        </div>
        @empty
        <p class="p-6 text-center text-gray-500">{{ __('wallet::app.shop.wallet.no-transactions') }}</p>
        @endforelse
    </div>

    {{ $transactions->links() }}
</div>
@stop
