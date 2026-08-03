<x-admin::layouts>
    <x-slot:title>
        Wallet: {{ $customer['name'] }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        {{-- Header Section with Quick Actions --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            Wallet: {{ $customer['name'] }}
                        </h1>
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            {{ $customer['status'] }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $customer['email'] }}
                    </p>
                </div>
            </div>

            {{-- Quick Action Buttons --}}
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                >
                    + Add Adjustment
                </button>

                <button
                    type="button"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/50 shadow-sm"
                >
                    Freeze Wallet
                </button>
            </div>
        </div>

        {{-- 3-Column Balance Statistics Grid --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-wallet::stat-card
                :title="trans('Total Balance')"
                :value="core()->formatBasePrice($balances['total'])"
                icon="icon-dollar"
                colorClass="text-blue-600 dark:text-blue-400"
            />

            <x-wallet::stat-card
                :title="trans('Available Balance')"
                :value="core()->formatBasePrice($balances['available'])"
                icon="icon-wallet"
                colorClass="text-emerald-600 dark:text-emerald-400"
            />

            <x-wallet::stat-card
                :title="trans('Held Balance')"
                :value="core()->formatBasePrice($balances['held'])"
                icon="icon-lock"
                colorClass="text-amber-600 dark:text-amber-400"
            />
        </div>

        {{-- Main Narrative Timeline Section --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        Transaction History (Narrative Timeline)
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Chronological account activity narrative for support and accounting audit.
                    </p>
                </div>
            </div>

            <x-wallet::timeline :events="$timeline" />
        </div>
    </div>
</x-admin::layouts>
