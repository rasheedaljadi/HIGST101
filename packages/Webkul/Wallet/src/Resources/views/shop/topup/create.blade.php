<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        إيداع رصيد في المحفظة | محفظة هايست
    </x-slot:title>

    <!-- Navigation Menu (Desktop) -->
    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <!-- Main Content Area -->
    <div class="flex-auto mx-4 max-md:mx-6 max-sm:mx-4">
        {{-- Header Section --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <!-- Back Button for Mobile -->
                <a
                    class="grid md:hidden"
                    href="{{ route('shop.wallet.index') }}"
                >
                    <span class="text-2xl icon-arrow-left rtl:icon-arrow-right"></span>
                </a>

                <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                    إيداع رصيد في المحفظة
                </h2>
            </div>

            <a
                href="{{ route('shop.wallet.index') }}"
                class="secondary-button border-zinc-200 px-5 py-2.5 font-normal max-md:rounded-lg max-md:py-2 max-sm:py-1.5 max-sm:text-sm"
            >
                العودة للمحفظة
            </a>
        </div>

        {{-- Interactive Top-Up Form Container --}}
        <div v-pre class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
            
            {{-- STEP 1: Amount Selection --}}
            <div id="step_1_container" class="flex flex-col gap-6">
                <div>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                        1. تحديد مبلغ الإيداع
                    </h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-gray-400">
                        اختر أحد المبالغ الجاهزة أو أدخل المبلغ المطلوب يدويًا.
                    </p>
                </div>

                {{-- Quick Amounts Grid --}}
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($quickAmounts as $quickAmount)
                        <button
                            type="button"
                            onclick="selectQuickAmount({{ $quickAmount }}, this)"
                            class="quick-amount-btn flex flex-col items-center justify-center rounded-xl border border-zinc-200 bg-white p-5 text-center transition-all duration-200 hover:border-zinc-400 dark:border-gray-800 dark:bg-gray-900"
                            data-amount="{{ $quickAmount }}"
                        >
                            <span class="text-xs text-zinc-400 font-normal">إضافة</span>
                            <span class="text-xl font-bold text-zinc-900 dark:text-white mt-1">${{ $quickAmount }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Custom Amount Input --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-gray-800">
                    <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-gray-300">
                        أو أدخل مبلغاً مخصصاً ($)
                    </label>

                    <div class="relative">
                        <span class="absolute right-3.5 top-3 text-zinc-400 font-bold">$</span>
                        <input
                            type="number"
                            id="custom_amount_input"
                            min="1"
                            step="1"
                            oninput="onCustomAmountInput(this.value)"
                            placeholder="مثال: 150"
                            class="w-full rounded-xl border border-zinc-300 bg-white py-3 pr-8 pl-4 text-sm text-zinc-800 focus:border-zinc-900 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                    </div>
                </div>

                {{-- Step 1 Action Button --}}
                <button
                    type="button"
                    onclick="goToStep2()"
                    class="primary-button w-full justify-center py-3.5 text-base font-medium rounded-xl"
                >
                    المتابعة إلى اختيار طريقة الدفع ←
                </button>
            </div>

            {{-- STEP 2: Active Payment Method Selection --}}
            <div id="step_2_container" class="hidden flex-col gap-6">
                <div class="flex items-center justify-between border-b border-zinc-100 pb-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                            2. اختيار طريقة الدفع المفعلة
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-gray-400">
                            اختر من بين طرق الدفع المفعلة رسمياً في منصة هايست.
                        </p>
                    </div>

                    <div class="text-left">
                        <span class="text-xs text-zinc-400">مبلغ الإيداع</span>
                        <p id="display_selected_amount" class="text-2xl font-bold text-zinc-900 dark:text-white">$0</p>
                    </div>
                </div>

                @if (count($paymentMethods) > 0)
                    {{-- Active Payment Methods List --}}
                    <div class="flex flex-col gap-3">
                        @foreach ($paymentMethods as $index => $payment)
                            <div
                                onclick="selectPaymentMethod('{{ $payment['method'] }}', this)"
                                class="payment-method-card flex cursor-pointer items-center justify-between rounded-xl border p-4 transition-all duration-200 {{ $index === 0 ? 'border-zinc-900 bg-zinc-50/80 dark:border-white dark:bg-gray-800' : 'border-zinc-200 dark:border-gray-800 hover:border-zinc-400' }}"
                                data-method="{{ $payment['method'] }}"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="radio-circle flex h-5 w-5 items-center justify-center rounded-full border-2 {{ $index === 0 ? 'border-zinc-900 bg-zinc-900 dark:border-white dark:bg-white' : 'border-zinc-300 dark:border-gray-700' }}">
                                        <div class="radio-dot h-2 w-2 rounded-full bg-white dark:bg-zinc-900 {{ $index === 0 ? '' : 'hidden' }}"></div>
                                    </div>

                                    @if (!empty($payment['image']))
                                        <img src="{{ $payment['image'] }}" class="h-6 w-auto object-contain" alt="{{ $payment['method_title'] }}" />
                                    @endif

                                    <div>
                                        <span class="font-bold text-sm text-zinc-900 dark:text-white">{{ $payment['method_title'] }}</span>
                                        @if (!empty($payment['description']))
                                            <p class="text-xs text-zinc-500 dark:text-gray-400">{{ $payment['description'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-3 py-1 rounded-full">
                                    مفعل آمن
                                </span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Step 2 Actions & Form --}}
                    <form action="{{ route('shop.wallet.topup.store') }}" method="POST" id="topup_submit_form">
                        @csrf
                        <input type="hidden" name="amount" id="final_amount_input" value="0" />
                        <input type="hidden" name="method" id="final_method_input" value="{{ count($paymentMethods) > 0 ? $paymentMethods[0]['method'] : '' }}" />

                        <div class="flex items-center gap-4 pt-4">
                            <button
                                type="button"
                                onclick="goToStep1()"
                                class="secondary-button w-1/3 justify-center py-3.5 text-base font-normal rounded-xl"
                            >
                                ← السابق
                            </button>

                            <button
                                type="submit"
                                id="submit_topup_btn"
                                class="primary-button w-2/3 justify-center py-3.5 text-base font-medium rounded-xl"
                            >
                                تأكيد الإيداع والدفع ←
                            </button>
                        </div>
                    </form>
                @else
                    {{-- No Active Payment Methods Fallback --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-center text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                        <p class="font-bold">⚠️ لا توجد طرق دفع مفعلة في النظام حالياً</p>
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">يرجى التواصل مع إدارة المتجر لتفعيل بوابة الدفع المناسبة.</p>
                    </div>

                    <button
                        type="button"
                        onclick="goToStep1()"
                        class="secondary-button w-full justify-center py-3 text-sm font-normal rounded-xl mt-4"
                    >
                        ← الرجوع للخلف
                    </button>
                @endif
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            let currentSelectedAmount = 0;
            let currentSelectedMethod = "{{ count($paymentMethods) > 0 ? $paymentMethods[0]['method'] : '' }}";

            function selectQuickAmount(amount, element) {
                currentSelectedAmount = amount;
                document.getElementById('custom_amount_input').value = '';
                document.getElementById('final_amount_input').value = amount;

                document.querySelectorAll('.quick-amount-btn').forEach(btn => {
                    btn.classList.remove('border-2', 'border-zinc-900', 'bg-zinc-50', 'dark:border-white');
                    btn.classList.add('border', 'border-zinc-200', 'bg-white');
                });

                element.classList.remove('border', 'border-zinc-200', 'bg-white');
                element.classList.add('border-2', 'border-zinc-900', 'bg-zinc-50', 'dark:border-white');
            }

            function onCustomAmountInput(val) {
                let amount = parseFloat(val) || 0;
                currentSelectedAmount = amount;
                document.getElementById('final_amount_input').value = amount;

                document.querySelectorAll('.quick-amount-btn').forEach(btn => {
                    btn.classList.remove('border-2', 'border-zinc-900', 'bg-zinc-50', 'dark:border-white');
                    btn.classList.add('border', 'border-zinc-200', 'bg-white');
                });
            }

            function goToStep2() {
                if (!currentSelectedAmount || currentSelectedAmount <= 0) {
                    alert('يرجى اختيار أو إدخال مبلغ الإيداع أولاً.');
                    return;
                }

                document.getElementById('display_selected_amount').innerText = '$' + currentSelectedAmount;
                document.getElementById('step_1_container').classList.add('hidden');
                document.getElementById('step_2_container').classList.remove('hidden');
                document.getElementById('step_2_container').classList.add('flex');
            }

            function goToStep1() {
                document.getElementById('step_2_container').classList.add('hidden');
                document.getElementById('step_2_container').classList.remove('flex');
                document.getElementById('step_1_container').classList.remove('hidden');
            }

            function selectPaymentMethod(methodKey, element) {
                currentSelectedMethod = methodKey;
                document.getElementById('final_method_input').value = methodKey;

                document.querySelectorAll('.payment-method-card').forEach(card => {
                    card.classList.remove('border-zinc-900', 'bg-zinc-50/80', 'dark:border-white');
                    card.classList.add('border-zinc-200');

                    let circle = card.querySelector('.radio-circle');
                    let dot = card.querySelector('.radio-dot');
                    if (circle) {
                        circle.classList.remove('border-zinc-900', 'bg-zinc-900', 'dark:border-white', 'dark:bg-white');
                        circle.classList.add('border-zinc-300');
                    }
                    if (dot) dot.classList.add('hidden');
                });

                element.classList.remove('border-zinc-200');
                element.classList.add('border-zinc-900', 'bg-zinc-50/80', 'dark:border-white');

                let circle = element.querySelector('.radio-circle');
                let dot = element.querySelector('.radio-dot');
                if (circle) {
                    circle.classList.remove('border-zinc-300');
                    circle.classList.add('border-zinc-900', 'bg-zinc-900', 'dark:border-white', 'dark:bg-white');
                }
                if (dot) dot.classList.remove('hidden');
            }
        </script>
    @endpushOnce
</x-shop::layouts.account>
