{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.before') !!}

<v-shipping-methods
    :methods="shippingMethods"
    @processing="stepForward"
    @processed="stepProcessed"
    @rate-selected="onRateSelected"
>
    <!-- Shipping Method Shimmer Effect -->
    <x-shop::shimmer.checkout.onepage.shipping-method />
</v-shipping-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.shipping_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-shipping-methods-template"
    >
        <div class="mb-7 max-md:mb-0">
            <template v-if="! methods">
                <!-- Shipping Method Shimmer Effect -->
                <x-shop::shimmer.checkout.onepage.shipping-method />
            </template>

            <template v-else>
                <!-- Accordion Blade Component -->
                <x-shop::accordion class="overflow-hidden !border-b-0 max-md:rounded-lg max-md:!border-none max-md:!bg-gray-100">
                    <!-- Accordion Blade Component Header -->
                    <x-slot:header class="px-0 py-4 max-md:p-3 max-md:text-sm max-md:font-medium max-sm:p-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-base">
                                @lang('shop::app.checkout.onepage.shipping.shipping-method')
                            </h2>
                        </div>
                    </x-slot>

                    <!-- Accordion Blade Component Content -->
                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:rounded-t-none max-md:border max-md:border-t-0 max-md:!p-4">
                        <div class="flex flex-col gap-6">
                            <div class="flex flex-wrap gap-8 max-md:gap-4 max-sm:gap-2.5">
                                <template v-for="method in methods">
                                    {!! view_render_event('bagisto.shop.checkout.onepage.shipping_method.before') !!}

                                    <div
                                        class="relative max-w-[218px] select-none max-md:max-w-full max-md:flex-auto"
                                        v-for="rate in method.rates"
                                    >
                                        <input 
                                            type="radio"
                                            name="shipping_method"
                                            :id="rate.method"
                                            :value="rate.method"
                                            class="peer hidden"
                                            v-model="selectedShippingMethod"
                                            @change="onShippingMethodChange(rate.method)"
                                        >

                                        <label 
                                            class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                            :for="rate.method"
                                        >
                                        </label>

                                        <label 
                                            class="block cursor-pointer rounded-xl border border-zinc-200 p-5 max-sm:flex max-sm:gap-4 max-sm:rounded-lg max-sm:px-4 max-sm:py-2.5"
                                            :for="rate.method"
                                        >
                                            <span class="icon-flate-rate text-6xl text-navyBlue max-sm:text-5xl"></span>

                                            <div>
                                                <p class="mt-1.5 text-2xl font-semibold max-md:text-base">
                                                    @{{ rate.base_formatted_price }}
                                                </p>
                                                
                                                <p class="mt-2.5 text-xs font-medium max-md:mt-1 max-sm:mt-0 max-sm:font-normal max-sm:text-zinc-500">
                                                    <span class="font-medium">@{{ rate.method_title }}</span> - @{{ rate.method_description }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    {!! view_render_event('bagisto.shop.checkout.onepage.shipping_method.after') !!}
                                </template>
                            </div>

                            <!-- Delivery Points Selector -->
                            <div 
                                v-if="isDeliveryPointSelected && availableDeliveryPoints.length"
                                class="rounded-2xl border border-purple-200 bg-purple-50/40 p-5 dark:border-purple-900/50 dark:bg-purple-950/20"
                            >
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl">🏪</span>
                                        <h4 class="text-base font-bold text-zinc-900 dark:text-white">
                                            اختر نقطة الاستلام المناسبة لك:
                                        </h4>
                                    </div>
                                    <span class="text-xs text-purple-700 dark:text-purple-300 font-medium bg-purple-100 dark:bg-purple-900/50 px-2.5 py-1 rounded-full">
                                        يتم الحفظ والتطبيق فوراً
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div
                                        v-for="point in availableDeliveryPoints"
                                        :key="point.id"
                                        @click="selectDeliveryPoint(point.id)"
                                        :class="[
                                            'cursor-pointer rounded-xl border p-4 transition-all relative',
                                            selectedDeliveryPointId === point.id
                                                ? 'border-purple-600 bg-white shadow-md ring-2 ring-purple-600/30 dark:bg-gray-800'
                                                : 'border-zinc-200 bg-white/70 hover:border-purple-300 dark:border-gray-700 dark:bg-gray-800/60'
                                        ]"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span 
                                                        :class="[
                                                            'h-4 w-4 rounded-full border flex items-center justify-center',
                                                            selectedDeliveryPointId === point.id 
                                                                ? 'border-purple-600 bg-purple-600' 
                                                                : 'border-zinc-300'
                                                        ]"
                                                    >
                                                        <span v-if="selectedDeliveryPointId === point.id" class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                    </span>
                                                    <span class="font-bold text-zinc-900 dark:text-white text-sm">
                                                        @{{ point.name_ar || point.name }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-zinc-600 dark:text-gray-300 ltr:pl-6 rtl:pr-6">
                                                    📍 @{{ point.address }}
                                                </p>
                                                <p v-if="point.contact_phone" class="mt-1 text-xs text-zinc-500 dark:text-gray-400 ltr:pl-6 rtl:pr-6">
                                                    📞 @{{ point.contact_phone }}
                                                </p>
                                            </div>

                                            <span 
                                                v-if="selectedDeliveryPointId === point.id"
                                                class="text-xs font-bold text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/60 px-2 py-0.5 rounded-md"
                                            >
                                                ✓ محدد
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-shop::accordion>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-shipping-methods', {
            template: '#v-shipping-methods-template',

            props: {
                methods: {
                    type: Object,
                    required: true,
                    default: () => null,
                },
            },

            emits: ['processing', 'processed', 'rate-selected'],

            data() {
                return {
                    selectedShippingMethod: '',
                    selectedDeliveryPointId: null,
                    allDeliveryPoints: @json(\Webkul\DeliveryManagement\Models\DeliveryPoint::where('is_active', true)->get()),
                };
            },

            computed: {
                isDeliveryPointSelected() {
                    return Boolean(this.selectedShippingMethod && (
                        this.selectedShippingMethod.includes('point') || 
                        this.selectedShippingMethod.includes('pickup')
                    ));
                },

                availableDeliveryPoints() {
                    return this.allDeliveryPoints;
                }
            },

            mounted() {
                if (this.allDeliveryPoints.length > 0) {
                    this.selectedDeliveryPointId = this.allDeliveryPoints[0].id;
                }
            },

            methods: {
                getRateByMethod(method) {
                    if (! this.methods) return null;
                    for (let m of Object.values(this.methods)) {
                        if (m.rates) {
                            for (let r of m.rates) {
                                if (r.method === method) {
                                    return r;
                                }
                            }
                        }
                    }
                    return null;
                },

                onShippingMethodChange(method) {
                    this.selectedShippingMethod = method;

                    const rate = this.getRateByMethod(method);
                    if (rate) {
                        this.$emit('rate-selected', rate);
                    }

                    if (this.isDeliveryPointSelected) {
                        if (! this.selectedDeliveryPointId && this.availableDeliveryPoints.length > 0) {
                            this.selectedDeliveryPointId = this.availableDeliveryPoints[0].id;
                        }
                        this.store(method, this.selectedDeliveryPointId);
                    } else {
                        this.store(method);
                    }
                },

                selectDeliveryPoint(pointId) {
                    this.selectedDeliveryPointId = pointId;

                    if (this.selectedShippingMethod) {
                        const rate = this.getRateByMethod(this.selectedShippingMethod);
                        if (rate) {
                            this.$emit('rate-selected', rate);
                        }
                        this.store(this.selectedShippingMethod, pointId);
                    }
                },

                store(selectedMethod, deliveryPointId = null) {
                    this.$emit('processing', 'payment');

                    const payload = {
                        shipping_method: selectedMethod,
                    };

                    if (deliveryPointId) {
                        payload.delivery_point_id = deliveryPointId;
                    }

                    this.$axios.post("{{ route('shop.checkout.onepage.shipping_methods.store') }}", payload)
                        .then(response => {
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                this.$emit('processed', response.data);
                            }
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: error.response?.data?.message || 'حدث خطأ أثناء تحديد وسيلة الشحن.'
                            });

                            if (error.response?.data?.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },
            },
        });
    </script>
@endpushonce
