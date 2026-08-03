<x-admin::layouts>
    <x-slot:title>
        HIGEST Wallet — Financial Governance Dashboard
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            Wallet Financial Governance & Risk Metrics
        </h1>

        {{-- Top Metrics Grid --}}
        <div class="grid grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase text-gray-500">Total System Liability</p>
                <p class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1">
                    {{ core()->formatBasePrice($totalLiability) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">All active & suspended customer balances</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase text-gray-500">Available Liquid Balance</p>
                <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ core()->formatBasePrice($availableLiability) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">Ready for spending or withdrawal</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase text-gray-500">Held Pending Balance</p>
                <p class="text-2xl font-extrabold text-amber-500 mt-1">
                    {{ core()->formatBasePrice($heldLiability) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">Frozen in withdrawal processing</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase text-gray-500">Pending Withdrawals Queue</p>
                <p class="text-2xl font-extrabold text-rose-500 mt-1">
                    {{ core()->formatBasePrice($pendingWithdrawalTotal) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $pendingWithdrawalCount }} requests awaiting transfer</p>
            </div>
        </div>

        {{-- Monthly Movement Breakdown --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Monthly Top-Up Volume</p>
                <p class="text-xl font-bold text-green-600 mt-1">+{{ core()->formatBasePrice($monthlyTopUps) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Monthly Refund Credits</p>
                <p class="text-xl font-bold text-teal-600 mt-1">+{{ core()->formatBasePrice($monthlyRefunds) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">Monthly Wallet Payments</p>
                <p class="text-xl font-bold text-indigo-600 mt-1">-{{ core()->formatBasePrice($monthlyWalletPayments) }}</p>
            </div>
        </div>

        {{-- Recent Audit Reconciliations --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Recent Daily Ledger Reconciliations</h2>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($recentReconciliations as $recon)
                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">
                                Audit Run #{{ $recon->id }} — {{ $recon->run_at->format('Y-m-d H:i') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                Audited {{ $recon->total_wallets_audited }} wallets | Liability: {{ core()->formatBasePrice($recon->total_system_liability) }}
                            </p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $recon->status === 'clean' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ strtoupper($recon->status) }} ({{ $recon->discrepancies_count }} Mismatches)
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">No automated reconciliation runs logged yet. Run <code class="bg-gray-100 p-1 rounded">php artisan wallet:verify-ledger</code>.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin::layouts>
