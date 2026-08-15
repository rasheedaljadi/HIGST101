<!-- Coupon Vue Component -->
<v-coupon 
    :cart="cart"
    @coupon-applied="getCart"
    @coupon-removed="getCart"
>
</v-coupon>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-coupon-template"
    >
        <div class="flex items-center justify-between text-right py-1 gap-2">
            <p class="text-base max-md:font-normal max-sm:text-sm whitespace-nowrap">
                @{{ cart.coupon_code ? "@lang('shop::app.checkout.coupon.applied')" : "@lang('shop::app.checkout.coupon.discount')" }}
            </p>

            {!! view_render_event('bagisto.shop.checkout.cart.coupon.before') !!}

            <!-- Apply Coupon Form (Inline) -->
            <div v-if="! cart.coupon_code" class="flex-1 max-w-[280px]">
                <x-shop::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, applyCoupon)" class="flex items-center gap-2 justify-end">
                        {!! view_render_event('bagisto.shop.checkout.cart.coupon.coupon_form_controls.before') !!}

                        <div class="relative flex-1">
                            <x-shop::form.control-group class="!mb-0">
                                <x-shop::form.control-group.control
                                    type="text"
                                    class="!mb-0 !py-1.5 !px-3 text-sm rounded-lg border border-gray-300 w-full focus:border-navyBlue max-sm:!py-1 max-sm:!px-2 max-sm:text-xs"
                                    name="code"
                                    rules="required"
                                    :placeholder="trans('shop::app.checkout.coupon.enter-your-code')"
                                />

                                <x-shop::form.control-group.error
                                    class="absolute top-full text-[11px] text-red-500 whitespace-nowrap ltr:left-0 rtl:right-0"
                                    control-name="code"
                                />
                            </x-shop::form.control-group>
                        </div>

                        <x-shop::button
                            class="primary-button rounded-lg !px-4 !py-1.5 text-sm whitespace-nowrap max-sm:!px-2 max-sm:!py-1 max-sm:text-xs"
                            :title="trans('shop::app.checkout.coupon.button-title')"
                            ::loading="isStoring"
                            ::disabled="isStoring"
                        />

                        {!! view_render_event('bagisto.shop.checkout.cart.coupon.coupon_form_controls.after') !!}
                    </form>
                </x-shop::form>
            </div>

            <!-- Applied Coupon Information Container -->
            <div 
                class="font-small flex items-center gap-2 text-xs"
                v-if="cart.coupon_code"
            >
                <p 
                    class="text-base font-medium text-navyBlue max-sm:text-sm"
                    title="@lang('shop::app.checkout.coupon.applied')"
                >
                    "@{{ cart.coupon_code }}"
                </p>

                <span 
                    class="icon-cancel cursor-pointer text-2xl max-sm:text-base text-red-500"
                    title="@lang('shop::app.checkout.coupon.remove')"
                    @click="destroyCoupon"
                >
                </span>
            </div>

            {!! view_render_event('bagisto.shop.checkout.cart.coupon.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-coupon', {
            template: '#v-coupon-template',
            
            props: ['cart'],

            data() {
                return {
                    isStoring: false,
                }
            },

            methods: {
                applyCoupon(params, { resetForm }) {
                    this.isStoring = true;

                    this.$axios.post("{{ route('shop.api.checkout.cart.coupon.apply') }}", params)
                        .then((response) => {
                            this.isStoring = false;

                            this.$emit('coupon-applied');
                  
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            resetForm();
                        })
                        .catch((error) => {
                            this.isStoring = false;

                            if (error.response && [400, 422].includes(error.response.status)) {
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });

                                resetForm();

                                return;
                            }

                            this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message || 'Error' });
                        });
                },

                destroyCoupon() {
                    this.$axios.delete("{{ route('shop.api.checkout.cart.coupon.remove') }}", {
                            '_token': "{{ csrf_token() }}"
                        })
                        .then((response) => {
                            this.$emit('coupon-removed');

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => console.log(error));
                },
            }
        })
    </script>
@endpushonce