@push('styles')
    <style>
        /* HIGEST Brand Styling & Visual Enhancements */
        .primary-button {
            background: linear-gradient(135deg, #061738 0%, #0e2b63 100%) !important;
            box-shadow: 0 4px 14px rgba(6, 23, 56, 0.25) !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .primary-button:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 20px rgba(6, 23, 56, 0.35) !important;
        }

        .primary-button:active {
            transform: translateY(0) !important;
        }

        /* Input Controls Refinement */
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            border-radius: 0.75rem !important;
            border-color: #e4e4e7 !important;
            background-color: #fafafa !important;
            transition: all 0.2s ease !important;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        select:focus {
            background-color: #ffffff !important;
            border-color: #061738 !important;
            box-shadow: 0 0 0 4px rgba(6, 23, 56, 0.08) !important;
        }

        /* Card Container Enhancements */
        #steps-container > div,
        .sticky > div {
            border-radius: 1rem !important;
        }
    </style>
@endpush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.checkout.onepage.index.checkout')
    </x-slot>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.before') !!}

    <!-- Page Header -->
    <div class="flex-wrap bg-white/90 backdrop-blur-md sticky top-0 z-50 shadow-sm border-b border-gray-100 mb-6">
        <div class="flex w-full justify-between items-center px-[60px] py-3.5 max-lg:px-8 max-sm:px-4">
            <div class="flex items-center gap-x-4">
                <a
                    href="{{ route('shop.home.index') }}"
                    class="flex items-center gap-2"
                    aria-label="{{ config('app.name') }}"
                >
                    <img
                        src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                        width="131"
                        height="29"
                        class="h-8 w-auto object-contain"
                    >
                </a>
                <span class="hidden md:inline-block h-5 w-px bg-gray-200"></span>
                <span class="hidden md:flex items-center gap-1.5 text-xs font-semibold text-zinc-700 bg-gray-100 px-3 py-1 rounded-full border border-gray-200/60">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    متجر هايست الرسمـي | HIGEST Store
                </span>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200/60">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    دفع آمن وتشخير 256-bit SSL
                </div>

                @guest('customer')
                    @include('shop::checkout.login')
                @endguest
            </div>
        </div>
    </div>

    {!! view_render_event('bagisto.shop.checkout.onepage.header.after') !!}

    <!-- Page Content -->
    <div class="container px-[60px] max-lg:px-8 max-sm:px-4">

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.before') !!}

        <!-- Premium Back Button -->
        <div class="flex items-center justify-between my-5">
            <a
                href="{{ route('shop.checkout.cart.index') }}"
                class="inline-flex items-center gap-2.5 px-4 py-2 text-sm font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 rounded-xl shadow-xs hover:shadow-md transition-all duration-200 hover:text-navyBlue group"
                title="العودة إلى عربة التسوق"
            >
                <div class="flex items-center justify-center w-6 h-6 rounded-lg bg-slate-100 group-hover:bg-navyBlue/10 text-slate-600 group-hover:text-navyBlue transition-colors">
                    <svg class="w-4 h-4 transform rtl:rotate-0 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </div>
                <span>العودة إلى سلة التسوق</span>
            </a>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 bg-slate-100/70 px-3 py-1.5 rounded-lg border border-slate-200/60 max-sm:hidden">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                خطوة إتمام الطلب والدفع
            </div>
        </div>

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.after') !!}

        <!-- Checkout Vue Component -->
        <v-checkout>
            <!-- Shimmer Effect -->
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-checkout-template"
        >
            <template v-if="! cart">
                <!-- Shimmer Effect -->
                <x-shop::shimmer.checkout.onepage />
            </template>

            <template v-else>
                <div class="grid grid-cols-[1fr_auto] gap-8 max-lg:grid-cols-[1fr] max-md:gap-5">
                    <!-- Included Checkout Summary Blade File For Mobile view -->
                    <div class="hidden max-md:block">
                        @include('shop::checkout.onepage.summary')
                    </div>

                    <div
                        class="overflow-y-auto max-md:grid max-md:gap-4"
                        id="steps-container"
                    >
                        <!-- Included Addresses Blade File -->
                        <template v-if="['address', 'shipping', 'payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.address')
                        </template>

                        <!-- Included Shipping Methods Blade File -->
                        <template v-if="cart.have_stockable_items && ['shipping', 'payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.shipping')
                        </template>

                        <!-- Included Payment Methods Blade File -->
                        <template v-if="['payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.payment')
                        </template>

                        <!-- Step 2: Order Summary & Receipt Upload for Manual Transfer -->
                        <div 
                            v-if="manualStep2Active && cart && ['offline_payments', 'moneytransfer'].includes(cart.payment_method)"
                            id="manual-step2-container"
                            class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-col gap-6"
                        >
                            {{-- Custom System Alert Modal --}}
                            <div v-if="showReceiptModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                                <div class="relative w-full max-w-[420px] transform overflow-hidden rounded-[32px] bg-white p-8 text-center shadow-2xl dark:bg-gray-900 border border-zinc-100 dark:border-gray-800 flex flex-col items-center gap-4">
                                    <div class="relative flex items-center justify-center my-2">
                                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-amber-50/90 border border-amber-100 dark:bg-amber-950/40 dark:border-amber-900/50 shadow-inner">
                                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white dark:bg-gray-800 shadow-md border border-amber-200/70 dark:border-amber-900/70">
                                                <span class="text-3xl text-amber-500">⚠️</span>
                                            </div>
                                        </div>
                                    </div>

                                    <h3 class="text-xl font-extrabold text-zinc-900 dark:text-white">
                                        إرفاق صورة إشعار التحويل المالي
                                    </h3>

                                    <div class="flex items-center justify-center w-full my-0.5 gap-2">
                                        <div class="h-[1px] w-12 bg-gradient-to-r from-transparent to-amber-300"></div>
                                        <div class="h-1.5 w-1.5 rotate-45 bg-amber-400 rounded-[1px]"></div>
                                        <div class="h-[1px] w-12 bg-gradient-to-l from-transparent to-amber-300"></div>
                                    </div>

                                    <p class="text-sm font-medium text-zinc-600 dark:text-gray-300 leading-relaxed">
                                        يرجى إرفاق صورة واضحة لإشعار <strong class="text-zinc-900 dark:text-white font-bold">التحويل المالي</strong> (المستند / الفاتورة) أولاً لإتمام طلب الشراء.
                                    </p>

                                    <button
                                        type="button"
                                        @click="showReceiptModal = false"
                                        style="background-color: #0f172a !important; color: #ffffff !important;"
                                        class="w-full max-w-[220px] inline-flex items-center justify-center gap-2 py-3 px-6 rounded-full text-white font-bold text-sm shadow-xl hover:opacity-90 active:scale-95 mt-2 cursor-pointer border-0"
                                    >
                                        <span style="color: #ffffff !important; font-weight: 700;">حسناً، فهمت</span>
                                        <span class="text-base">🖼️</span>
                                    </button>
                                </div>
                            </div>

                            {{-- STEP 2 Header --}}
                            <div>
                                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                                    2. ملخص الطلب وإرفاق إشعار التحويل المالي
                                </h3>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-gray-400">
                                    يرجى مراجعة ملخص العملية وأدناه إرفاق صورة إشعار أو وصل التحويل لخصم وإعتماد الطلب.
                                </p>
                            </div>

                            {{-- Transaction Summary Card --}}
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-gray-800 dark:bg-gray-800/50 flex flex-col gap-4">
                                <h4 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">تفاصيل ملخص الطلب</h4>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1 rounded-xl bg-white p-4 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                        <span class="text-xs text-zinc-500 dark:text-gray-400">إجمالي المبلغ المطلوب دفعه:</span>
                                        <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">@{{ cart.formatted_grand_total }}</span>
                                    </div>

                                    <div class="flex flex-col gap-1 rounded-xl bg-white p-4 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                        <span class="text-xs text-zinc-500 dark:text-gray-400">طريقة الدفع والحساب:</span>
                                        <span class="text-base font-bold text-zinc-900 dark:text-white">@{{ selectedAccountDisplayName }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="flex flex-col gap-1 rounded-xl bg-white p-3.5 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                        <span class="text-xs text-zinc-500 dark:text-gray-400">اسم المستلم المعرف:</span>
                                        <span class="text-sm font-bold text-zinc-900 dark:text-white">@{{ selectedAccountRecipientName || '—' }}</span>
                                    </div>

                                    <div class="flex items-center justify-between rounded-xl bg-white p-3.5 border border-zinc-200 dark:bg-gray-900 dark:border-gray-700">
                                        <div>
                                            <span class="text-xs text-zinc-500 dark:text-gray-400 block">رقم الحساب / المحفظة:</span>
                                            <span class="font-mono text-sm font-bold text-zinc-900 dark:text-white">@{{ selectedAccountIdentifier || '—' }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="copyAccountId"
                                            class="inline-flex items-center gap-1 rounded-md border border-zinc-200 bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition-all"
                                        >
                                            📋 <span>@{{ copySuccess ? '✓ تم النسخ!' : 'نسخ' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-col items-center justify-center text-center gap-2 rounded-2xl bg-amber-50/90 p-5 border border-amber-300/80 shadow-sm dark:bg-amber-950/50 dark:border-amber-900/50">
                                    <span class="text-base sm:text-lg font-extrabold text-amber-900 dark:text-amber-200 text-center block">
                                        📌 تعليمات التحويل:
                                    </span>
                                    <p class="text-sm sm:text-base font-bold text-amber-800 dark:text-amber-300 text-center leading-relaxed max-w-2xl mx-auto whitespace-pre-line">
                                        <template v-if="selectedAccountInstructions">@{{ selectedAccountInstructions }}<br><br></template>
                                        ⚠️ في حال أن المبلغ الذي تم تحويله لا يساوي <span style="color: #dc2626; font-weight: 800; background-color: #fef2f2; padding: 2px 8px; border-radius: 6px; border: 1px solid #fecaca; display: inline-block; direction: ltr; margin: 0 4px;">@{{ cart.formatted_grand_total }}</span> سيتم رفض الطلب.
                                    </p>
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

                                <input
                                    type="file"
                                    ref="receiptInput"
                                    accept="image/*"
                                    @change="onReceiptSelected"
                                    class="hidden"
                                />

                                <div
                                    v-if="!receiptPreviewUrl"
                                    @click="$refs.receiptInput.click()"
                                    class="group cursor-pointer flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-300 bg-zinc-50/50 p-8 text-center transition-all duration-200 hover:border-zinc-900 hover:bg-zinc-100/70 dark:border-gray-700 dark:bg-gray-800/40 dark:hover:border-white"
                                >
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-2xl shadow-sm border border-zinc-200 dark:bg-gray-800 dark:border-gray-700 group-hover:scale-110 transition-transform">
                                        📷
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-zinc-800 dark:text-white">
                                        اضغط هنا لاختيار أو إرفاق صورة إشعار التحويل
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-400 dark:text-gray-500">
                                        يدعم الصور بتنسيق JPG, PNG, WEBP (بحد أقصى 10 ميجابايت)
                                    </p>
                                </div>

                                <div v-else class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <img :src="receiptPreviewUrl" class="h-16 w-16 object-cover rounded-xl border border-zinc-200 dark:border-gray-700" alt="معاينة الإشعار" />
                                            <div>
                                                <p class="text-sm font-bold text-zinc-900 dark:text-white truncate max-w-[220px]">@{{ receiptFileName }}</p>
                                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">✓ جاهز للإرسال والمعاينة</span>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            @click="removeReceipt"
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
                                    @click="manualStep2Active = false"
                                    class="secondary-button w-1/3 justify-center py-3.5 text-base font-normal rounded-xl"
                                >
                                    ← تعديل البيانات
                                </button>

                                <button
                                    type="button"
                                    @click="placeOrder"
                                    :disabled="isPlacingOrder"
                                    class="primary-button w-2/3 justify-center py-3.5 text-base font-medium rounded-xl flex items-center justify-center gap-2"
                                >
                                    <span>تأكيد وإرسال الطلب 🚀</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Included Checkout Summary Blade File For Desktop view -->
                    <div class="sticky top-8 block h-max w-[442px] max-w-full max-lg:w-auto max-lg:max-w-[442px] ltr:pl-8 max-lg:ltr:pl-0 rtl:pr-8 max-lg:rtl:pr-0">
                        <div class="block max-md:hidden">
                            @include('shop::checkout.onepage.summary')
                        </div>

                        <div
                            class="flex justify-end"
                            v-if="canPlaceOrder"
                        >
                            <template v-if="cart.payment_method == 'paypal_smart_button'">
                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                                <!-- Paypal Smart Button Vue Component -->
                                <v-paypal-smart-button></v-paypal-smart-button>

                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                            </template>

                            <template v-else>
                                <x-shop::button
                                    type="button"
                                    class="primary-button w-max rounded-2xl bg-navyBlue px-11 py-3 max-md:mb-4 max-md:w-full max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                                    :title="trans('shop::app.checkout.onepage.summary.place-order')"
                                    ::disabled="isPlacingOrder"
                                    ::loading="isPlacingOrder"
                                    @click="placeOrder"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </script>

        <script type="module">
            app.component('v-checkout', {
                template: '#v-checkout-template',

                data() {
                    return {
                        cart: null,

                        displayTax: {
                            prices: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_prices') }}",

                            subtotal: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_subtotal') }}",
                            
                            shipping: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_shipping_amount') }}",
                        },

                        isPlacingOrder: false,

                        currentStep: 'address',

                        shippingMethods: null,

                        paymentMethods: null,

                        canPlaceOrder: false,

                        manualStep2Active: false,

                        selectedOfflineAccountId: null,

                        showReceiptModal: false,

                        receiptFile: null,

                        receiptPreviewUrl: null,

                        receiptFileName: null,

                        copySuccess: false,
                    }
                },

                mounted() {
                    this.getCart();

                    if (window.offlineAccounts && window.offlineAccounts.length) {
                        this.selectedOfflineAccountId = window.offlineAccounts[0].id;
                    }

                    this.$emitter.on('offline-account-selected', id => {
                        this.selectedOfflineAccountId = id;
                    });
                },

                computed: {
                    selectedOfflineAccount() {
                        if (!window.offlineAccounts || !window.offlineAccounts.length) return null;
                        const selId = this.selectedOfflineAccountId || (window.offlineAccounts[0] ? window.offlineAccounts[0].id : null);
                        return window.offlineAccounts.find(a => a.id == selId) || window.offlineAccounts[0];
                    },

                    selectedAccountDisplayName() {
                        const acc = this.selectedOfflineAccount;
                        if (!acc) return 'تحويل مالي';
                        return 'تحويل مالي - ' + (acc.account ? (acc.account.display_name || acc.account.provider_name) : '');
                    },

                    selectedAccountRecipientName() {
                        const acc = this.selectedOfflineAccount;
                        return acc && acc.account ? acc.account.recipient_name : '';
                    },

                    selectedAccountIdentifier() {
                        const acc = this.selectedOfflineAccount;
                        return acc ? acc.account_identifier : '';
                    },

                    selectedAccountInstructions() {
                        const acc = this.selectedOfflineAccount;
                        return acc ? (acc.transfer_instructions || '') : '';
                    },
                },

                methods: {
                    getCart() {
                        this.$axios.get("{{ route('shop.checkout.onepage.summary') }}")
                            .then(response => {
                                this.cart = response.data.data;

                                this.scrollToCurrentStep();
                            })
                            .catch(error => {});
                    },

                    stepForward(step) {
                        this.currentStep = step;

                        if (step == 'review') {
                            this.canPlaceOrder = true;

                            return;
                        }

                        this.canPlaceOrder = false;

                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = null;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = null;
                        }
                    },

                    stepProcessed(data) {
                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = data;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = data;
                        }

                        this.getCart();
                    },

                    scrollToCurrentStep() {
                        let container = document.getElementById('steps-container');

                        if (! container) {
                            return;
                        }

                        container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
                    },

                    onReceiptSelected(event) {
                        const file = event.target.files[0];
                        if (file) {
                            this.receiptFile = file;
                            this.receiptFileName = file.name;
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.receiptPreviewUrl = e.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    },

                    removeReceipt() {
                        this.receiptFile = null;
                        this.receiptPreviewUrl = null;
                        this.receiptFileName = null;
                        if (this.$refs.receiptInput) {
                            this.$refs.receiptInput.value = '';
                        }
                    },

                    copyAccountId() {
                        const accId = this.selectedAccountIdentifier;
                        if (accId) {
                            navigator.clipboard.writeText(accId);
                            this.copySuccess = true;
                            setTimeout(() => { this.copySuccess = false; }, 2000);
                        }
                    },

                    placeOrder() {
                        const isManualPayment = this.cart && ['offline_payments', 'moneytransfer'].includes(this.cart.payment_method);

                        if (isManualPayment && !this.manualStep2Active) {
                            this.manualStep2Active = true;
                            this.$nextTick(() => {
                                const elem = document.getElementById('manual-step2-container');
                                if (elem) elem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            });
                            return;
                        }

                        if (isManualPayment && !this.receiptFile) {
                            this.showReceiptModal = true;
                            return;
                        }

                        this.isPlacingOrder = true;

                        const formData = new FormData();
                        if (this.receiptFile) {
                            formData.append('receipt', this.receiptFile);
                        }

                        this.$axios.post("{{ route('shop.checkout.onepage.orders.store') }}", formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        })
                        .then(response => {
                            if (response.data.data.redirect) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                            }

                            this.isPlacingOrder = false;
                        })
                        .catch(error => {
                            this.isPlacingOrder = false;

                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'حدث خطأ في تقديم الطلب'
                            });
                        });
                    }
                },
            });
        </script>
    @endpushonce
</x-shop::layouts>
