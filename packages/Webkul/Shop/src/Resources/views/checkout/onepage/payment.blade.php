{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.before') !!}

<v-payment-methods
    :methods="paymentMethods"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <x-shop::shimmer.checkout.onepage.payment-method />
</v-payment-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-payment-methods-template"
    >
        <div class="mb-7 max-md:last:!mb-0">
            <template v-if="! methods">
                <!-- Payment Method shimmer Effect -->
                <x-shop::shimmer.checkout.onepage.payment-method />
            </template>
    
            <template v-else>
                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.before') !!}

                <!-- Accordion Blade Component -->
                <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                    <!-- Accordion Blade Component Header -->
                    <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                        
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-base">
                                @lang('shop::app.checkout.onepage.payment.payment-method')
                            </h2>
                        </div>
                    </x-slot>
    
                    <!-- Accordion Blade Component Content -->
                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">
                        <div class="flex flex-wrap gap-7 max-md:gap-4 max-sm:gap-2.5">
                            <div 
                                class="relative cursor-pointer max-md:max-w-full max-md:flex-auto"
                                v-for="(payment, index) in methods"
                            >
                                {!! view_render_event('bagisto.shop.checkout.payment-method.before') !!}

                                <input 
                                    type="radio" 
                                    name="payment[method]" 
                                    :value="payment.payment"
                                    :id="payment.method"
                                    class="peer hidden"
                                    @change="store(payment)"
                                >
    
                                <label 
                                    :for="payment.method" 
                                    class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                >
                                </label>

                                <label 
                                    :for="payment.method" 
                                    class="block w-[190px] cursor-pointer rounded-xl border border-zinc-200 p-5 max-md:flex max-md:w-full max-md:gap-5 max-md:rounded-lg max-sm:gap-4 max-sm:px-4 max-sm:py-2.5"
                                >
                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.before') !!}

                                    <img
                                        class="max-h-11 max-w-14"
                                        :src="payment.image"
                                        width="55"
                                        height="55"
                                        :alt="payment.method_title"
                                        :title="payment.method_title"
                                    />

                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.after') !!}

                                    <div>
                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.before') !!}

                                        <p class="mt-1.5 text-sm font-semibold max-md:mt-1 max-sm:mt-0">
                                            @{{ payment.method_title }}
                                        </p>
                                        
                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.after') !!}

                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.before') !!}

                                        <p class="mt-2.5 text-xs font-medium text-zinc-500 max-md:mt-1 max-sm:mt-0">
                                            @{{ payment.description }}
                                        </p> 

                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.after') !!}
    
                                    </div>
                                </label>

                                {!! view_render_event('bagisto.shop.checkout.payment-method.after') !!}

                                <!-- Todo implement the additionalDetails -->
                                {{-- \Webkul\Payment\Payment::getAdditionalDetails($payment['method'] --}}
                            </div>
                        </div>

                        <!-- Sub-selector for Offline Payment Destinations (Matches Top-Up Screen Design) -->
                        <div v-if="(selectedMethodCode === 'offline_payments' || selectedMethodCode === 'moneytransfer') && offlineAccounts.length > 0" class="mt-6 p-5 rounded-2xl border border-zinc-200 bg-zinc-50/60 dark:border-gray-800 dark:bg-gray-900/60 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">
                                    اختر حساب التحويل:
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div 
                                    v-for="dest in offlineAccounts" 
                                    :key="dest.id"
                                    class="relative flex flex-col items-center justify-between p-4 rounded-2xl cursor-pointer transition-all duration-200 shadow-sm"
                                    :style="selectedOfflineAccountId === dest.id ? 'border: 3px solid #2563eb; background-color: #eff6ff; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);' : 'border: 2px solid #e4e4e7; background-color: #ffffff;'"
                                    @click="selectOfflineAccount(dest)"
                                >
                                    {{-- Top row: Radio + Selection Badge --}}
                                    <div class="w-full flex items-center justify-between mb-2">
                                        <span 
                                            class="text-xl"
                                            :class="[selectedOfflineAccountId === dest.id ? 'icon-radio-select text-blue-600' : 'icon-radio-unselect text-zinc-400']"
                                        ></span>

                                        <span 
                                            v-if="selectedOfflineAccountId === dest.id"
                                            class="text-xs font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 shadow-sm"
                                        >
                                            ✓ محدد
                                        </span>
                                    </div>

                                    {{-- Account Logo --}}
                                    <div class="flex h-16 w-full items-center justify-center my-2 pointer-events-none">
                                        <img v-if="dest.account && dest.account.logo_path" :src="'/storage/' + dest.account.logo_path" class="max-h-14 max-w-[120px] object-contain" :alt="dest.account ? dest.account.display_name : ''">
                                        <span v-else class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 text-2xl dark:bg-gray-700">💳</span>
                                    </div>

                                    {{-- Account Name ONLY --}}
                                    <span class="text-sm font-bold text-zinc-900 dark:text-white text-center line-clamp-2 mt-1 pointer-events-none">
                                        @{{ dest.account ? (dest.account.display_name || dest.account.provider_name) : '' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-shop::accordion>

                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.after') !!}
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-payment-methods', {
            template: '#v-payment-methods-template',

            props: {
                methods: {
                    type: Object,
                    required: true,
                    default: () => null,
                },
            },

            emits: ['processing', 'processed'],

            data() {
                return {
                    selectedMethodCode: null,
                    selectedOfflineAccountId: null,
                    offlineAccounts: window.offlineAccounts || [],
                };
            },

            mounted() {
                if (this.offlineAccounts.length > 0) {
                    this.selectedOfflineAccountId = this.offlineAccounts[0].id;
                }
            },

            methods: {
                selectOfflineAccount(dest) {
                    this.selectedOfflineAccountId = dest.id;
                    this.$emitter.emit('offline-account-selected', dest.id);
                    const offlineMethod = this.methods.find(m => m.method === 'offline_payments' || m.method === 'moneytransfer');
                    if (offlineMethod) {
                        offlineMethod.selected_offline_destination_id = dest.id;
                        offlineMethod.selected_offline_account_id = dest.id;
                        this.store(offlineMethod);
                    }
                },

                store(selectedMethod) {
                    this.selectedMethodCode = selectedMethod.method;

                    if (selectedMethod.method === 'offline_payments' || selectedMethod.method === 'moneytransfer') {
                        if (! this.selectedOfflineAccountId && this.offlineAccounts.length > 0) {
                            this.selectedOfflineAccountId = this.offlineAccounts[0].id;
                        }
                        selectedMethod.selected_offline_destination_id = this.selectedOfflineAccountId;
                        selectedMethod.selected_offline_account_id = this.selectedOfflineAccountId;
                    }

                    this.$emit('processing', 'review');

                    this.$axios.post("{{ route('shop.checkout.onepage.payment_methods.store') }}", {
                            payment: selectedMethod
                        })
                        .then(response => {
                            this.$emit('processed', response.data.cart);

                            // Used in mobile view. 
                            if (window.innerWidth <= 768) {
                                window.scrollTo({
                                    top: document.body.scrollHeight,
                                    behavior: 'smooth'
                                });
                            }
                        })
                        .catch(error => {
                            this.$emit('processing', 'payment');

                            if (error.response.data.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });

        window.offlineAccounts = @json($offlineAccounts ?? []);
    </script>
@endpushonce
