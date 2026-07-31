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

                        <!-- Sub-selector for Offline Payment Destinations -->
                        <div v-if="selectedMethodCode === 'offline_payments' && offlineAccounts.length > 0" class="mt-6 p-5 border border-zinc-200 rounded-xl bg-white dark:bg-gray-900 dark:border-gray-800">
                            <h3 class="text-lg font-medium text-zinc-800 dark:text-white mb-4">@lang('offline_payments::app.shop.checkout.select-account')</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div 
                                    v-for="dest in offlineAccounts" 
                                    :key="dest.id"
                                    class="flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                    :class="[selectedOfflineAccountId === dest.id ? 'border-navyBlue bg-zinc-50 dark:bg-zinc-800 ring-2 ring-navyBlue/20 dark:border-navyBlue' : 'border-zinc-200 dark:border-gray-800']"
                                    @click="selectOfflineAccount(dest)"
                                >
                                    <div class="flex-shrink-0 mt-0.5">
                                        <span 
                                            class="text-xl"
                                            :class="[selectedOfflineAccountId === dest.id ? 'icon-radio-select text-navyBlue' : 'icon-radio-unselect text-zinc-400']"
                                        ></span>
                                    </div>
                                    <img v-if="dest.account && dest.account.logo_path" :src="'/storage/' + dest.account.logo_path" class="h-10 w-10 object-cover rounded border bg-white flex-shrink-0">
                                    <div class="flex-grow">
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">@{{ dest.account ? dest.account.display_name : '' }}</p>
                                        <p class="text-xs text-zinc-500 mt-1">
                                            <strong>@{{ dest.account ? dest.account.provider_name : '' }}:</strong> @{{ dest.account_identifier }}
                                        </p>
                                        <p class="text-xs text-zinc-500" v-if="dest.account">
                                            <strong>@lang('offline_payments::app.admin.form.recipient-name'):</strong> @{{ dest.account.recipient_name }}
                                        </p>
                                        <p class="text-xs text-zinc-500" v-if="dest.swift_code">
                                            <strong>SWIFT:</strong> @{{ dest.swift_code }}
                                        </p>
                                        <div v-if="dest.transfer_instructions" class="text-xs text-zinc-600 dark:text-zinc-400 mt-2 border-t pt-2 border-zinc-100 dark:border-zinc-800 whitespace-pre-line">
                                            @{{ dest.transfer_instructions }}
                                        </div>
                                    </div>
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
                    const offlineMethod = this.methods.find(m => m.method === 'offline_payments');
                    if (offlineMethod) {
                        offlineMethod.selected_offline_destination_id = dest.id;
                        offlineMethod.selected_offline_account_id = dest.id;
                        this.store(offlineMethod);
                    }
                },

                store(selectedMethod) {
                    this.selectedMethodCode = selectedMethod.method;

                    if (selectedMethod.method === 'offline_payments') {
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
