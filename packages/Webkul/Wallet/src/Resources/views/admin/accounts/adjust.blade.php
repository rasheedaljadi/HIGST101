<x-admin::layouts>
    <x-slot:title>
        Manual Wallet Adjustment
    </x-slot:title>

    <div class="mx-auto max-w-3xl flex flex-col gap-6 p-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    Manual Wallet Adjustment
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Perform direct credit or debit adjustments to customer wallet balances.
                </p>
            </div>

            <a
                href="{{ route('admin.wallet.accounts.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                ← Back to Accounts
            </a>
        </div>

        {{-- Top Context Card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Customer</span>
                    <p class="mt-1 text-base font-bold text-gray-800 dark:text-white">{{ $customer['name'] }}</p>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</span>
                    <p class="mt-1 text-base font-medium text-gray-700 dark:text-gray-300">{{ $customer['email'] }}</p>
                </div>

                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Current Balance</span>
                    <p class="mt-1 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ core()->formatBasePrice($wallet['current_balance']) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Alpine.js Two-Step Confirmation Context --}}
        <div
            x-data="{
                step: 1,
                type: 'increase',
                amount: '',
                reason: '',
                reference: ''
            }"
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >

            {{-- STEP 1: Input Form --}}
            <div x-show="step === 1" class="flex flex-col gap-5">
                <div class="border-b border-gray-100 pb-3 dark:border-gray-800">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        Step 1: Adjustment Details
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Specify adjustment direction, amount, and formal reason.
                    </p>
                </div>

                {{-- Action Type Selection --}}
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Adjustment Type <span class="text-red-500">*</span>
                    </label>

                    <div class="grid grid-cols-2 gap-4">
                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 p-3 font-semibold transition-all"
                            :class="type === 'increase' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400'"
                        >
                            <input type="radio" name="adjustment_type" value="increase" x-model="type" class="sr-only" />
                            <span class="text-lg">➕</span>
                            <span>Increase Balance (+)</span>
                        </label>

                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 p-3 font-semibold transition-all"
                            :class="type === 'decrease' ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'border-gray-200 dark:border-gray-800 text-gray-600 dark:text-gray-400'"
                        >
                            <input type="radio" name="adjustment_type" value="decrease" x-model="type" class="sr-only" />
                            <span class="text-lg">➖</span>
                            <span>Decrease Balance (-)</span>
                        </label>
                    </div>
                </div>

                {{-- Amount Input --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Amount ($) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        x-model="amount"
                        placeholder="0.00"
                        class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                </div>

                {{-- Reason Input --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Reason / Note <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        rows="3"
                        x-model="reason"
                        placeholder="Provide detailed justification for accounting audit..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    ></textarea>
                </div>

                {{-- External Reference (Optional) --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        External Reference / Ticket ID <span class="text-xs font-normal text-gray-400">(Optional)</span>
                    </label>
                    <input
                        type="text"
                        x-model="reference"
                        placeholder="e.g. TICKET-9921 or ORDER-1002"
                        class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                </div>

                {{-- Step 1 Action Button --}}
                <div class="pt-2">
                    <button
                        type="button"
                        @click="if (amount && reason) { step = 2; } else { alert('Please enter both amount and reason before reviewing.'); }"
                        class="w-full rounded-lg bg-blue-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                    >
                        Review Adjustment →
                    </button>
                </div>
            </div>

            {{-- STEP 2: Strict Confirmation Block --}}
            <div x-show="step === 2" x-cloak class="flex flex-col gap-5">
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-700/60 dark:bg-amber-950/30">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="text-xl font-bold text-amber-950 dark:text-amber-200">
                                Confirm Financial Adjustment
                            </h3>
                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                This action directly modifies ledger balance and cannot be automatically undone.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg bg-white/80 p-4 shadow-sm backdrop-blur dark:bg-gray-900/80">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                            You are about to
                            <span class="font-extrabold uppercase text-blue-600 dark:text-blue-400" x-text="type"></span>
                            the wallet balance by
                            <span class="font-extrabold text-2xl text-emerald-600 dark:text-emerald-400" x-text="'$' + amount"></span>.
                        </h4>

                        <div class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-800">
                            <div class="flex justify-between py-2">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Target Account</span>
                                <span class="font-bold text-gray-800 dark:text-white">{{ $customer['name'] }} ({{ $customer['email'] }})</span>
                            </div>

                            <div class="flex justify-between py-2">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Reason</span>
                                <span class="font-semibold text-gray-800 dark:text-white" x-text="reason"></span>
                            </div>

                            <template x-if="reference">
                                <div class="flex justify-between py-2">
                                    <span class="font-medium text-gray-500 dark:text-gray-400">Reference</span>
                                    <span class="font-mono font-semibold text-gray-800 dark:text-white" x-text="reference"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Step 2 Buttons --}}
                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="step = 1"
                        class="w-1/3 rounded-lg border border-gray-300 bg-white py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        ← Back to Edit
                    </button>

                    <form action="{{ route('admin.wallet.accounts.adjust', $wallet['id']) }}" method="POST" class="w-2/3">
                        @csrf
                        <input type="hidden" name="type" :value="type" />
                        <input type="hidden" name="amount" :value="amount" />
                        <input type="hidden" name="reason" :value="reason" />
                        <input type="hidden" name="reference" :value="reference" />

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-emerald-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                        >
                            ✓ Confirm & Execute Adjustment
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-admin::layouts>
