@extends('shop::layouts.account')

@section('page_title')
    Wallet Account Statement
@stop

@section('account-content')
<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
            Wallet Account Statement
        </p>
        <div class="flex items-center gap-2">
            <a href="{{ route('shop.customer.wallet.statement.download', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="primary-button text-sm">
                📄 Download PDF
            </a>
            <a href="{{ route('shop.customer.wallet.statement.csv', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="secondary-button text-sm">
                📊 Export CSV
            </a>
        </div>
    </div>

    {{-- Filter Form --}}
    <form action="{{ route('shop.customer.wallet.statement') }}" method="GET" class="flex gap-4 items-end bg-gray-50 p-4 rounded-xl dark:bg-gray-800">
        <div>
            <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">From Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border-gray-300 p-2 text-sm" />
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">To Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border-gray-300 p-2 text-sm" />
        </div>
        <button type="submit" class="secondary-button text-sm">Filter</button>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs text-gray-500">Opening Balance</p>
            <p class="text-xl font-bold text-gray-800 dark:text-white mt-1">{{ core()->formatBasePrice($openingBalance) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs text-gray-500">Total Credits (+)</p>
            <p class="text-xl font-bold text-green-600 mt-1">+{{ core()->formatBasePrice($periodCredits) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs text-gray-500">Total Debits (-)</p>
            <p class="text-xl font-bold text-red-500 mt-1">-{{ core()->formatBasePrice($periodDebits) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-xs text-gray-500">Closing Balance</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ core()->formatBasePrice($closingBalance) }}</p>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($transactions as $txn)
        <div class="flex items-center justify-between p-4">
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $txn->type }}</p>
                <p class="text-xs text-gray-500">{{ $txn->description }}</p>
                <p class="text-xs text-gray-400">{{ $txn->created_at->format('Y-m-d H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="font-bold {{ $txn->isCredit() ? 'text-green-600' : 'text-red-500' }}">
                    {{ $txn->isCredit() ? '+' : '-' }}{{ core()->formatBasePrice($txn->amount) }}
                </p>
                <p class="text-xs text-gray-400">Balance: {{ core()->formatBasePrice($txn->running_balance) }}</p>
            </div>
        </div>
        @empty
        <p class="p-6 text-center text-gray-500">No transactions in selected period.</p>
        @endforelse
    </div>
</div>
@stop
