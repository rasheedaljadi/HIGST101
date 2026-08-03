@extends('shop::layouts.account')

@section('page_title')
    {{ __('wallet::app.shop.withdrawal.title') }}
@stop

@section('account-content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
            {{ __('wallet::app.shop.withdrawal.title') }}
        </p>
        <a href="{{ route('shop.customer.wallet.withdrawal.create') }}" class="primary-button text-sm">
            + {{ __('wallet::app.shop.withdrawal.title') }}
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($withdrawals as $withdrawal)
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($withdrawal->amount) }}</p>
                <p class="text-xs text-gray-500">{{ $withdrawal->masked_iban }}</p>
                <p class="text-xs text-gray-400">{{ $withdrawal->created_at->format('Y-m-d') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-medium
                {{ $withdrawal->status === 'completed' ? 'bg-green-100 text-green-700' : ($withdrawal->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                {{ ucfirst($withdrawal->status) }}
            </span>
        </div>
        @empty
        <p class="p-6 text-center text-gray-500">{{ __('wallet::app.shop.wallet.no-transactions') }}</p>
        @endforelse
    </div>

    {{ $withdrawals->links() }}
</div>
@stop
