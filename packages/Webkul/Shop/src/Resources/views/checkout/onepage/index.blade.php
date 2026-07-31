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
                    }
                },

                mounted() {
                    this.getCart();
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

                    placeOrder() {
                        this.isPlacingOrder = true;

                        this.$axios.post('{{ route('shop.checkout.onepage.orders.store') }}')
                            .then(response => {
                                if (response.data.data.redirect) {
                                    window.location.href = response.data.data.redirect_url;
                                } else {
                                    window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                                }

                                this.isPlacingOrder = false;
                            })
                            .catch(error => {
                                this.isPlacingOrder = false

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    }
                },
            });
        </script>
    @endpushonce
</x-shop::layouts>
