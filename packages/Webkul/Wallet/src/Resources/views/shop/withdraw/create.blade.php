<x-shop::layouts.account>
    <x-slot:title>
        طلب سحب رصيد
    </x-slot:title>

    {{-- Desktop Sidebar Navigation --}}
    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="flex-auto mx-4 max-md:mx-6 max-sm:mx-4" v-pre id="withdrawal-app-container">
        {{-- Header Section --}}
        <div class="flex items-center justify-between pb-4 border-b border-zinc-200 dark:border-zinc-800 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    طلب سحب رصيد
                </h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    تحويل الرصيد المتاح في محفظتك إلى حسابك البنكي أو المحفظة الإلكترونية
                </p>
            </div>

            <a
                href="{{ route('shop.wallet.index') }}"
                class="secondary-button text-sm flex items-center gap-2"
            >
                ← العودة للمحفظة
            </a>
        </div>

        {{-- 2-Column Responsive Layout --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Main Form Column --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-sm">
                    
                    {{-- Available Balance Banner --}}
                    <div class="mb-6 flex items-center justify-between rounded-xl bg-emerald-50 p-4 border border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800/40">
                        <div>
                            <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300">الرصيد القابل للسحب</span>
                            <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                                {{ core()->formatBasePrice($availableBalance) }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-emerald-700 bg-emerald-100 dark:bg-emerald-900/50 dark:text-emerald-300">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            متاح فوراً للسحب
                        </span>
                    </div>

                    <form action="{{ route('shop.wallet.withdraw.store') }}" method="POST" id="withdrawal-form" class="flex flex-col gap-5">
                        @csrf
                        
                        {{-- Amount Field with Live Validation --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                مبلغ السحب المطلوب <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    name="amount"
                                    id="withdraw-amount"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0.00"
                                    class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm text-zinc-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                    oninput="validateWithdrawalForm()"
                                    required
                                />
                            </div>
                            <p id="amount-error" class="hidden mt-1.5 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                ⚠️ المبلغ يتجاوز رصيدك المتاح للسحب ({{ core()->formatBasePrice($availableBalance) }}).
                            </p>
                        </div>

                        {{-- Method Dropdown --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                طريقة السحب / تحويل المبلغ <span class="text-red-500">*</span>
                            </label>
                            <select
                                name="method"
                                id="withdraw-method"
                                class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm text-zinc-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                onchange="validateWithdrawalForm()"
                                required
                            >
                                <option value="">اختر طريقة السحب والتحويل...</option>
                                @foreach ($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Account Name Input --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                اسم صاحب الحساب / المستلم <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="account_name"
                                id="withdraw-account-name"
                                placeholder="الاسم الكامل كما هو مسجل في الحساب أو البنك"
                                class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm text-zinc-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                oninput="validateWithdrawalForm()"
                                required
                            />
                        </div>

                        {{-- Account Number / IBAN Input --}}
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                رقم الحساب أو الآيبان (IBAN) / رقم المحفظة <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="account_number"
                                id="withdraw-account-number"
                                placeholder="رقم الآيبان أو رقم الحساب البنكي أو رقم المحفظة"
                                class="w-full rounded-xl border border-zinc-300 bg-white p-3 text-sm text-zinc-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                                oninput="validateWithdrawalForm()"
                                required
                            />
                        </div>

                        {{-- Submit Button --}}
                        <div class="pt-2">
                            <button
                                type="submit"
                                id="withdraw-submit-btn"
                                class="primary-button w-full py-3.5 text-base font-bold shadow-sm"
                            >
                                تأكيد تقديم طلب السحب
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar Column: Recent Requests --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-zinc-200 p-6 dark:border-zinc-800 dark:bg-zinc-900 shadow-sm">
                    <h2 class="text-base font-bold text-zinc-900 dark:text-white mb-4 pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        طلبات السحب الأخيرة
                    </h2>

                    @if(count($recentWithdrawals) > 0)
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($recentWithdrawals as $item)
                                <div class="py-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold font-mono text-zinc-800 dark:text-white">
                                            {{ $item['id'] }}
                                        </p>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                            {{ $item['date'] }}
                                        </p>
                                    </div>

                                    <div class="text-left">
                                        <p class="text-sm font-bold text-zinc-900 dark:text-white">
                                            {{ $item['amount'] }}
                                        </p>
                                        <span class="inline-block mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item['color'] }}">
                                            {{ $item['status'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 text-center py-6">لا توجد طلبات سحب سابقة.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <script>
        const availableBalance = {{ (float) $availableBalance }};

        function validateWithdrawalForm() {
            const amountInput = document.getElementById('withdraw-amount');
            const methodSelect = document.getElementById('withdraw-method');
            const nameInput = document.getElementById('withdraw-account-name');
            const numberInput = document.getElementById('withdraw-account-number');
            const submitBtn = document.getElementById('withdraw-submit-btn');
            const errorMsg = document.getElementById('amount-error');

            const val = parseFloat(amountInput.value) || 0;

            if (val > availableBalance) {
                errorMsg.classList.remove('hidden');
            } else {
                errorMsg.classList.add('hidden');
            }

            const isValid = val > 0 && val <= availableBalance && methodSelect.value !== '' && nameInput.value.trim() !== '' && numberInput.value.trim() !== '';

            if (isValid) {
                submitBtn.removeAttribute('disabled');
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.setAttribute('disabled', 'disabled');
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            validateWithdrawalForm();
        });
    </script>
</x-shop::layouts.account>
