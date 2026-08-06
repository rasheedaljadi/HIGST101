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
        {{-- Custom System Alert Modal (Matches Proposed Mockup Design) --}}
        <div id="system_alert_modal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 transition-all duration-300">
            <div class="relative w-full max-w-[420px] transform overflow-hidden rounded-[32px] bg-white p-8 text-center shadow-2xl transition-all dark:bg-gray-900 border border-zinc-100 dark:border-gray-800 flex flex-col items-center gap-4">
                
                {{-- Glowing Icon Container with Sparkles --}}
                <div class="relative flex items-center justify-center my-2">
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-amber-50/90 border border-amber-100 dark:bg-amber-950/40 dark:border-amber-900/50 shadow-inner">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-md border border-amber-200/70 dark:border-amber-900/70">
                            <span class="text-3xl text-amber-500">⚠️</span>
                        </div>
                    </div>
                    <span class="absolute -top-1 left-2 text-xs text-amber-400 opacity-70">✦</span>
                    <span class="absolute bottom-1 right-1 text-xs text-amber-400 opacity-70">✦</span>
                    <span class="absolute top-2 right-0 text-[10px] text-amber-300 opacity-60">✧</span>
                </div>

                {{-- Modal Title --}}
                <h3 id="system_alert_title" class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight">
                    إرفاق صورة إشعار التحويل المالي
                </h3>

                {{-- Divider line with center diamond dot --}}
                <div class="flex items-center justify-center w-full my-0.5 gap-2">
                    <div class="h-[1px] w-12 bg-gradient-to-r from-transparent to-amber-300"></div>
                    <div class="h-1.5 w-1.5 rotate-45 bg-amber-400 rounded-[1px]"></div>
                    <div class="h-[1px] w-12 bg-gradient-to-l from-transparent to-amber-300"></div>
                </div>

                {{-- Modal Message Content --}}
                <p id="system_alert_message" class="text-sm font-medium text-zinc-600 dark:text-gray-300 leading-relaxed max-w-sm mx-auto px-1">
                    يرجى إرفاق صورة واضحة لإشعار <strong class="text-zinc-900 dark:text-white font-bold">التحويل المالي</strong> (المستند / الفاتورة) أولاً لإتمام طلب الإيداع.
                </p>

                {{-- Pill Button with Icon --}}
                <button
                    type="button"
                    onclick="closeSystemAlert()"
                    style="background-color: #0f172a !important; color: #ffffff !important;"
                    class="w-full max-w-[220px] inline-flex items-center justify-center gap-2 py-3 px-6 rounded-full text-white font-bold text-sm shadow-xl transition-all hover:opacity-90 active:scale-95 mt-2 cursor-pointer border-0"
                >
                    <span style="color: #ffffff !important; font-weight: 700;">حسناً، فهمت</span>
                    <span class="text-base">🖼️</span>
                </button>
            </div>
        </div>

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

        {{-- Multi-Step Top-Up Form --}}
        <form action="{{ route('shop.wallet.topup.store') }}" method="POST" enctype="multipart/form-data" id="topup_submit_form" onsubmit="return validateTopupForm(event)">
            @csrf
            <input type="hidden" name="amount" id="final_amount_input" value="0" />
            <input type="hidden" name="method" id="final_method_input" value="{{ count($paymentMethods) > 0 ? $paymentMethods[0]['method'] : '' }}" />
            <input type="hidden" name="offline_account_id" id="final_offline_account_input" value="{{ (isset($offlineAccounts) && count($offlineAccounts) > 0) ? $offlineAccounts[0]->id : '' }}" />

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div v-pre class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
                
                {{-- STEP 1: Combined Amount & Payment Method Selection --}}
                <div id="step_1_container" class="flex flex-col gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                            1. تحديد مبلغ الإيداع وطريقة الدفع
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-gray-400">
                            أدخل المبلغ المراد إيداعه واختر طريقة الدفع المناسبة.
                        </p>
                    </div>

                    {{-- Custom Amount Input Field --}}
                    <div>
                        <label class="mb-2 block text-sm font-bold text-zinc-800 dark:text-gray-200">
                            مبلغ الإيداع المطلـوب ($) <span class="text-rose-500">*</span>
                        </label>

                        <div class="flex rounded-xl border border-zinc-300 bg-white overflow-hidden focus-within:border-zinc-900 dark:border-gray-700 dark:bg-gray-900 shadow-sm">
                            <span class="inline-flex items-center justify-center bg-zinc-100 px-5 text-lg font-bold text-zinc-700 border-l border-zinc-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 select-none">
                                $
                            </span>
                            <input
                                type="number"
                                id="custom_amount_input"
                                min="1"
                                step="1"
                                oninput="onCustomAmountInput(this.value)"
                                placeholder="أدخل المبلغ هنا (مثال: 50)"
                                class="w-full bg-transparent py-4 px-4 text-base font-bold text-zinc-900 focus:outline-none dark:text-white"
                            />
                        </div>
                    </div>

                    {{-- Payment Methods Section --}}
                    <div class="border-t border-zinc-100 pt-5 dark:border-gray-800 flex flex-col gap-3">
                        <label class="block text-sm font-bold text-zinc-800 dark:text-gray-200">
                            اختر طريقة الدفع المناسبة <span class="text-rose-500">*</span>
                        </label>

                        @if (count($paymentMethods) > 0)
                            <div class="flex flex-col gap-3">
                                @foreach ($paymentMethods as $index => $payment)
                                    <div class="flex flex-col gap-2">
                                        {{-- Clean Payment Method Card (Logo + Name Only) --}}
                                        <label
                                            class="payment-method-card flex cursor-pointer items-center justify-between rounded-xl border border-zinc-200 p-4 transition-all duration-200 hover:border-zinc-400 has-[:checked]:border-zinc-900 has-[:checked]:bg-zinc-50/80 dark:border-gray-800 dark:has-[:checked]:border-white dark:has-[:checked]:bg-gray-800 shadow-sm"
                                            data-method="{{ $payment['method'] }}"
                                        >
                                            <div class="flex items-center gap-3.5">
                                                <input
                                                    type="radio"
                                                    name="payment_method_choice"
                                                    value="{{ $payment['method'] }}"
                                                    class="h-5 w-5 accent-zinc-900 dark:accent-white cursor-pointer"
                                                    {{ $index === 0 ? 'checked' : '' }}
                                                    onchange="onPaymentMethodChanged('{{ $payment['method'] }}')"
                                                />

                                                @if (!empty($payment['image']))
                                                    <img src="{{ $payment['image'] }}" class="h-8 w-auto object-contain" alt="{{ $payment['method_title'] }}" />
                                                @else
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-100 text-lg dark:bg-gray-700">💳</span>
                                                @endif

                                                <span class="font-bold text-base text-zinc-900 dark:text-white">{{ $payment['method_title'] }}</span>
                                            </div>
                                        </label>

                                        {{-- Offline Accounts Selection Box --}}
                                        @if (in_array($payment['method'], ['moneytransfer', 'offline_payments']) && isset($offlineAccounts) && count($offlineAccounts) > 0)
                                            <div id="offline_accounts_container" class="mr-6 max-md:mr-0 my-3 p-4 md:p-5 rounded-2xl border border-zinc-200 bg-zinc-50/60 dark:border-gray-800 dark:bg-gray-900/60 flex flex-col gap-4">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm font-bold text-zinc-800 dark:text-white">
                                                        اختر حساب التحويل:
                                                    </p>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                                    @foreach ($offlineAccounts as $accIndex => $dest)
                                                        <label
                                                            class="offline-account-card relative flex flex-col items-center justify-between p-4 rounded-2xl cursor-pointer transition-all duration-200"
                                                            style="{{ $accIndex === 0 ? 'border: 3px solid #2563eb; background-color: #eff6ff; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);' : 'border: 2px solid #e4e4e7; background-color: #ffffff;' }}"
                                                            onclick="selectOfflineAccount(this, '{{ $dest->id }}')"
                                                            data-account-name="{{ $dest->account->display_name ?? $dest->account->provider_name ?? '' }}"
                                                            data-provider-name="{{ $dest->account->provider_name ?? '' }}"
                                                            data-recipient-name="{{ $dest->account->recipient_name ?? '' }}"
                                                            data-account-identifier="{{ $dest->account_identifier ?? '' }}"
                                                            data-instructions="{{ $dest->transfer_instructions ?? '' }}"
                                                        >
                                                            {{-- Top row: Visible Radio + Selection Pill --}}
                                                            <div class="w-full flex items-center justify-between mb-2">
                                                                <input
                                                                    type="radio"
                                                                    name="offline_account_choice"
                                                                    value="{{ $dest->id }}"
                                                                    class="offline-account-radio h-5 w-5 accent-blue-600 cursor-pointer"
                                                                    {{ $accIndex === 0 ? 'checked' : '' }}
                                                                />

                                                                <span
                                                                    class="account-check-badge text-xs font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full items-center gap-1 shadow-sm"
                                                                    style="{{ $accIndex === 0 ? 'display: inline-flex;' : 'display: none;' }}"
                                                                >
                                                                    ✓ محدد
                                                                </span>
                                                            </div>

                                                            {{-- Account Logo --}}
                                                            <div class="flex h-16 w-full items-center justify-center my-2 pointer-events-none">
                                                                @if (!empty($dest->account->logo_path))
                                                                    <img
                                                                        src="{{ Storage::url($dest->account->logo_path) }}"
                                                                        class="max-h-14 max-w-[120px] object-contain"
                                                                        alt="{{ $dest->account->display_name ?? $dest->account->provider_name }}"
                                                                    />
                                                                @else
                                                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-2xl dark:bg-gray-700">
                                                                        💳
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            {{-- Account Name --}}
                                                            <span class="text-sm font-bold text-zinc-900 dark:text-white text-center line-clamp-2 mt-1 pointer-events-none">
                                                                {{ $dest->account->display_name ?? $dest->account->provider_name }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{-- No Active Payment Methods Fallback --}}
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-center text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                                <p class="font-bold">⚠️ لا توجد طرق دفع مفعلة في النظام حالياً</p>
                                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">يرجى التواصل مع إدارة المتجر لتفعيل بوابة الدفع المناسبة.</p>
                            </div>
                        @endif
                    </div>

                    {{-- Step 1 Continue Button --}}
                    <button
                        type="button"
                        onclick="goToStep2()"
                        class="primary-button w-full justify-center py-4 text-base font-bold rounded-xl shadow-md mt-2"
                    >
                        المتابعة لمراجعة وتأكيد العملية ←
                    </button>
                </div>

                {{-- STEP 2: Operation Summary & Receipt Attachment --}}
                <div id="step_2_container" class="hidden flex-col gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                            2. ملخص الإيداع وإرفاق إشعار التحويل المالي
                        </h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-gray-400">
                            يرجى مراجعة ملخص العملية وأدناه إرفاق صورة إشعار أو وصل التحويل لخصم الإيداع والاعتماد.
                        </p>
                    </div>

                    {{-- Transaction Summary Card --}}
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-gray-800 dark:bg-gray-800/50 flex flex-col gap-4">
                        <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">تفاصيل ملخص العملية</h4>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1 rounded-xl bg-white p-4 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-xs text-zinc-500 dark:text-gray-400">المبلغ المراد إيداعه:</span>
                                <span id="summary_amount_display" class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">$0</span>
                            </div>

                            <div class="flex flex-col gap-1 rounded-xl bg-white p-4 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-xs text-zinc-500 dark:text-gray-400">طريقة الدفع والحساب:</span>
                                <span id="summary_method_title" class="text-base font-bold text-zinc-900 dark:text-white">—</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1 rounded-xl bg-white p-3.5 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                <span class="text-xs text-zinc-500 dark:text-gray-400">اسم المستلم المعرف:</span>
                                <span id="summary_recipient_name" class="text-sm font-bold text-zinc-900 dark:text-white">—</span>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-white p-3.5 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                <div>
                                    <span class="text-xs text-zinc-500 dark:text-gray-400 block">رقم الحساب / المحفظة:</span>
                                    <span id="summary_account_id" class="font-mono text-sm font-bold text-zinc-900 dark:text-white">—</span>
                                </div>
                                <button
                                    type="button"
                                    onclick="copySummaryAccountId(this)"
                                    class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition-all"
                                >
                                    📋 <span>نسخ</span>
                                </button>
                            </div>
                        </div>

                        <div id="summary_instructions_box" class="hidden flex-col items-center justify-center text-center gap-2 rounded-2xl bg-amber-50/90 p-5 border border-amber-300/80 shadow-sm dark:bg-amber-950/50 dark:border-amber-900/50">
                            <span class="text-base sm:text-lg font-extrabold text-amber-900 dark:text-amber-200 text-center block">
                                📌 تعليمات التحويل:
                            </span>
                            <p id="summary_instructions" class="text-sm sm:text-base font-bold text-amber-800 dark:text-amber-300 text-center leading-relaxed max-w-2xl mx-auto whitespace-pre-line"></p>
                        </div>
                    </div>

                    {{-- Receipt Image Upload Area --}}
                    <div class="flex flex-col gap-2 pt-2">
                        <label class="block text-sm font-bold text-zinc-900 dark:text-white">
                            إرفاق صورة إشعار التحويل المالي <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-zinc-500 dark:text-gray-400">
                            يرجى رفع صورة واضحة لوصل التحويل أو لقطة شاشة من تطبيق المحفظة/البنك لسرعة المراجعة والتأكيد.
                        </p>

                        {{-- Hidden File Input --}}
                        <input
                            type="file"
                            name="receipt"
                            id="receipt_file_input"
                            accept="image/*"
                            onchange="onReceiptFileSelected(this)"
                            class="hidden"
                        />

                        {{-- Dropzone UI Box --}}
                        <div
                            id="receipt_upload_dropzone"
                            onclick="triggerReceiptUpload()"
                            class="group cursor-pointer flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center transition-all duration-200 hover:border-zinc-900 hover:bg-zinc-100/70 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-white"
                        >
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-2xl shadow-sm border border-zinc-200 dark:bg-gray-800 dark:border-gray-700 group-hover:scale-110 transition-transform">
                                📷
                            </div>
                            <p class="mt-3 text-sm font-bold text-zinc-800 dark:text-white">
                                اضغط هنا لااختيار أو إرفاق صورة إشعار التحويل
                            </p>
                            <p class="mt-1 text-xs text-zinc-400 dark:text-gray-500">
                                يدعم الصور بتنسيق JPG, PNG, WEBP (بحد أقصى 10 ميجابايت)
                            </p>
                        </div>

                        {{-- Image Preview Box --}}
                        <div id="receipt_preview_box" class="hidden flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img id="receipt_preview_img" src="" class="h-16 w-16 object-cover rounded-xl border border-zinc-200 dark:border-gray-700" alt="معاينة الإشعار" />
                                    <div>
                                        <p id="receipt_file_name" class="text-sm font-bold text-zinc-900 dark:text-white truncate max-w-[220px]"></p>
                                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">✓ جاهز للإرسال والمعاينة</span>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    onclick="removeReceiptImage()"
                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-400 transition-all"
                                >
                                    تغيير الصورة ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2 Submission Actions --}}
                    <div class="flex items-center gap-4 pt-4">
                        <button
                            type="button"
                            onclick="goBackToStep1()"
                            class="secondary-button w-1/3 justify-center py-3.5 text-base font-normal rounded-xl"
                        >
                            ← تعديل البيانات
                        </button>

                        <button
                            type="submit"
                            id="submit_topup_btn"
                            class="primary-button w-2/3 justify-center py-3.5 text-base font-medium rounded-xl"
                        >
                            تأكيد وإرسال طلب الإيداع 🚀
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @pushOnce('scripts')
        <script>
            let currentSelectedAmount = 0;
            let currentSelectedMethod = "{{ count($paymentMethods) > 0 ? $paymentMethods[0]['method'] : '' }}";

            function onCustomAmountInput(val) {
                let amount = parseFloat(val) || 0;
                currentSelectedAmount = amount;
                document.getElementById('final_amount_input').value = amount;
            }

            function showSystemAlert(message, title = 'تنبيه') {
                let titleElem = document.getElementById('system_alert_title');
                let msgElem = document.getElementById('system_alert_message');
                let modal = document.getElementById('system_alert_modal');

                if (titleElem) titleElem.innerText = title;
                if (msgElem) msgElem.innerHTML = message;
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }

            function closeSystemAlert() {
                let modal = document.getElementById('system_alert_modal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }

            function goToStep2() {
                if (!currentSelectedAmount || currentSelectedAmount <= 0) {
                    showSystemAlert('يرجى كتابة مبلغ الإيداع المطلوب أولاً.', 'تنبيه إدخال المبلغ');
                    return;
                }

                let formattedNum = parseFloat(currentSelectedAmount).toFixed(2);
                document.getElementById('summary_amount_display').innerText = '$ ' + formattedNum;

                let selectedOfflineRadio = document.querySelector('input[name="offline_account_choice"]:checked');
                let methodTitle = 'تحويل مالي';
                let accountName = '';
                let recipientName = '';
                let accountId = '';
                let instructions = '';

                if (selectedOfflineRadio) {
                    let parentCard = selectedOfflineRadio.closest('.offline-account-card');
                    if (parentCard) {
                        accountName = parentCard.dataset.accountName || '';
                        recipientName = parentCard.dataset.recipientName || '';
                        accountId = parentCard.dataset.accountIdentifier || '';
                        instructions = parentCard.dataset.instructions || '';
                    }
                }

                document.getElementById('summary_method_title').innerText = accountName ? (methodTitle + ' - ' + accountName) : methodTitle;
                document.getElementById('summary_recipient_name').innerText = recipientName || '—';
                document.getElementById('summary_account_id').innerText = accountId || '—';

                let instBox = document.getElementById('summary_instructions_box');
                let instElem = document.getElementById('summary_instructions');
                let baseInstructions = instructions ? instructions.trim() : '';

                let amountBadge = '<span style="color: #dc2626; font-weight: 800; background-color: #fef2f2; padding: 2px 8px; border-radius: 6px; border: 1px solid #fecaca; display: inline-block; direction: ltr; margin: 0 4px;">$ ' + formattedNum + '</span>';
                let warningLine = 'في حال أن المبلغ الذي تم إيداعه لا يساوي ' + amountBadge + ' سيتم رفض الطلب.';

                if (baseInstructions) {
                    instElem.innerHTML = baseInstructions + '<br/><br/>⚠️ ' + warningLine;
                } else {
                    instElem.innerHTML = '⚠️ ' + warningLine;
                }

                instBox.classList.remove('hidden');
                instBox.classList.add('flex');

                document.getElementById('step_1_container').classList.add('hidden');
                document.getElementById('step_2_container').classList.remove('hidden');
                document.getElementById('step_2_container').classList.add('flex');
            }

            function goBackToStep1() {
                document.getElementById('step_2_container').classList.add('hidden');
                document.getElementById('step_2_container').classList.remove('flex');
                document.getElementById('step_1_container').classList.remove('hidden');
                document.getElementById('step_1_container').classList.add('flex');
            }

            function onPaymentMethodChanged(methodKey) {
                currentSelectedMethod = methodKey;
                let hiddenInput = document.getElementById('final_method_input');
                if (hiddenInput) {
                    hiddenInput.value = methodKey;
                }
            }

            function selectOfflineAccount(cardElem, accId) {
                document.querySelectorAll('.offline-account-card').forEach(c => {
                    c.style.border = '2px solid #e4e4e7';
                    c.style.backgroundColor = '#ffffff';
                    c.style.boxShadow = 'none';
                    let r = c.querySelector('.offline-account-radio');
                    if (r) r.checked = false;
                    let b = c.querySelector('.account-check-badge');
                    if (b) b.style.display = 'none';
                });

                cardElem.style.border = '3px solid #2563eb';
                cardElem.style.backgroundColor = '#eff6ff';
                cardElem.style.boxShadow = '0 4px 14px rgba(37, 99, 235, 0.25)';
                let radio = cardElem.querySelector('.offline-account-radio');
                if (radio) radio.checked = true;
                let badge = cardElem.querySelector('.account-check-badge');
                if (badge) badge.style.display = 'inline-flex';

                let hiddenAccInput = document.getElementById('final_offline_account_input');
                if (hiddenAccInput) {
                    hiddenAccInput.value = accId;
                }
            }

            function validateTopupForm(event) {
                let receiptInput = document.getElementById('receipt_file_input');
                let selectedMethod = currentSelectedMethod || (document.getElementById('final_method_input') ? document.getElementById('final_method_input').value : '');
                let isOffline = ['moneytransfer', 'offline_payments'].includes(selectedMethod);

                if (isOffline) {
                    if (!receiptInput || !receiptInput.files || receiptInput.files.length === 0) {
                        if (event) event.preventDefault();
                        showSystemAlert(
                            'يرجى إرفاق صورة واضحة لإشعار <strong class="text-zinc-900 dark:text-white font-bold">التحويل المالي</strong> (المستند / الفاتورة)<br/>أولاً لإتمام طلب الإيداع.',
                            'إرفاق صورة إشعار التحويل المالي'
                        );

                        let dropzone = document.getElementById('receipt_upload_dropzone');
                        if (dropzone) {
                            dropzone.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            dropzone.style.border = '2px dashed #ef4444';
                            dropzone.style.backgroundColor = '#fef2f2';
                            setTimeout(() => {
                                dropzone.style.border = '';
                                dropzone.style.backgroundColor = '';
                            }, 4000);
                        }
                        return false;
                    }
                }
                return true;
            }

            function triggerReceiptUpload() {
                document.getElementById('receipt_file_input').click();
            }

            function onReceiptFileSelected(input) {
                if (input.files && input.files[0]) {
                    let file = input.files[0];
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('receipt_preview_img').src = e.target.result;
                        document.getElementById('receipt_file_name').innerText = file.name;
                        document.getElementById('receipt_upload_dropzone').classList.add('hidden');
                        document.getElementById('receipt_preview_box').classList.remove('hidden');
                        document.getElementById('receipt_preview_box').classList.add('flex');
                    };
                    reader.readAsDataURL(file);
                }
            }

            function removeReceiptImage() {
                let input = document.getElementById('receipt_file_input');
                input.value = '';
                document.getElementById('receipt_preview_img').src = '';
                document.getElementById('receipt_preview_box').classList.add('hidden');
                document.getElementById('receipt_preview_box').classList.remove('flex');
                document.getElementById('receipt_upload_dropzone').classList.remove('hidden');
            }

            function copySummaryAccountId(element) {
                let accId = document.getElementById('summary_account_id').innerText;
                if (accId && accId !== '—') {
                    copyToClipboard(accId, element);
                }
            }

            function copyToClipboard(text, element) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text);
                } else {
                    let textArea = document.createElement("textarea");
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand("copy");
                    document.body.removeChild(textArea);
                }

                let origContent = element.innerHTML;
                element.innerHTML = '✓ تم النسخ!';
                element.classList.add('bg-emerald-600', 'text-white');
                setTimeout(() => {
                    element.innerHTML = origContent;
                    element.classList.remove('bg-emerald-600', 'text-white');
                }, 2000);
            }
        </script>
    @endpushOnce
</x-shop::layouts.account>
