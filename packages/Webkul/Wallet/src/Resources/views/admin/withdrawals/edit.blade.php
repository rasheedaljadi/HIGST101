<x-admin::layouts>
    <x-slot:title>
        Process Withdrawal {{ $withdrawal['id'] }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    Process Withdrawal Request {{ $withdrawal['id'] }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Review security risk indicators and complete bank payout or reject request.
                </p>
            </div>
        </div>

        {{-- 2-Column Grid Layout --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Left Column: Information & Risk Indicator --}}
            <div class="flex flex-col gap-6">

                {{-- Risk Indicator Component --}}
                <x-wallet::withdrawal-risk-indicator
                    :level="$riskProfile['level']"
                    :colorClass="$riskProfile['colorClass']"
                    :factors="$riskProfile['factors']"
                />

                {{-- Request & Bank Details Card --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        Request & Bank Account Details
                    </h2>

                    <div class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-800">
                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Customer</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $withdrawal['customer'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Requested Amount</span>
                            <span class="text-lg font-extrabold text-gray-900 dark:text-white">{{ $withdrawal['amount'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Payout Method</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $withdrawal['method'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Bank Name</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $withdrawal['bank_name'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Account Name</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $withdrawal['account_name'] }}</span>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">Masked IBAN</span>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-sm font-bold tracking-widest text-gray-800 dark:bg-gray-800 dark:text-white">
                                    {{ $withdrawal['masked_iban'] }}
                                </span>

                                <button
                                    type="button"
                                    title="Reveal full IBAN"
                                    class="rounded-lg border border-gray-200 bg-white p-1.5 text-gray-500 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                                >
                                    👁️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Execution Forms --}}
            <div class="flex flex-col gap-6">

                {{-- Complete Withdrawal Card --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/20">
                    <h2 class="text-lg font-bold text-emerald-900 dark:text-emerald-300">
                        Complete Withdrawal (Payout Done)
                    </h2>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">
                        Enter the bank transfer transaction reference after sending funds.
                    </p>

                    <form action="{{ route('admin.wallet.withdrawals.complete', $withdrawal['raw_id']) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                Bank Reference ID <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="bank_reference_id"
                                required
                                placeholder="e.g. TR-998124589"
                                class="w-full rounded-lg border border-emerald-300 bg-white p-2.5 text-sm text-gray-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-emerald-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-emerald-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                        >
                            ✓ Confirm & Complete
                        </button>
                    </form>
                </div>

                {{-- Reject Withdrawal Card --}}
                <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 shadow-sm dark:border-red-800/40 dark:bg-red-950/20">
                    <h2 class="text-lg font-bold text-red-900 dark:text-red-300">
                        Reject Withdrawal
                    </h2>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">
                        Provide a clear reason. The held balance will be unlocked and returned to customer's wallet.
                    </p>

                    <form action="{{ route('admin.wallet.withdrawals.reject', $withdrawal['raw_id']) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-red-800 dark:text-red-300">
                                Rejection Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="rejection_reason"
                                required
                                rows="3"
                                placeholder="State reason for rejecting request..."
                                class="w-full rounded-lg border border-red-300 bg-white p-2.5 text-sm text-gray-800 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:border-red-700 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-red-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/50"
                        >
                            ✕ Reject Request
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-admin::layouts>
