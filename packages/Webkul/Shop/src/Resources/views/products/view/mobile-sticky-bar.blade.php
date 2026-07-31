<v-mobile-sticky-bar
    :product-id="{{ $product->id }}"
    :is-saleable="{{ ($pdpViewData['is_saleable'] ?? $product->isSaleable(1)) ? 'true' : 'false' }}"
    :price-html='@json($product->getTypeInstance()->getPriceHtml())'
    :product-type="'{{ $product->type }}'"
>
</v-mobile-sticky-bar>

@pushonce('scripts')
    <script
        type="text/x-template"
        id="v-mobile-sticky-bar-template"
    >
        <div
            v-if="isMounted"
            class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between gap-3 border-t border-zinc-200 bg-white p-3 shadow-2xl transition-all duration-300 ease-in-out 1180:hidden"
            :class="isVisible ? 'translate-y-0 opacity-100 pointer-events-auto' : 'translate-y-full opacity-0 pointer-events-none'"
        >
            <!-- Price Display -->
            <div class="flex flex-col">
                <span class="text-xs text-zinc-500 font-medium max-sm:text-[10px]">
                    @lang('shop::app.products.view.price')
                </span>
                <div 
                    class="text-lg font-bold text-navyBlue max-sm:text-base [&>*]:text-lg [&>*]:font-bold [&>*]:text-navyBlue"
                    v-html="priceHtml"
                >
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 flex-1 max-w-[260px]">
                <template v-if="! isSaleable">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-zinc-200 px-4 py-2.5 text-xs font-semibold text-zinc-500 cursor-not-allowed"
                        disabled
                    >
                        @lang('shop::app.products.view.out-of-stock')
                    </button>
                </template>

                <template v-else-if="requiresSelection">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-navyBlue px-4 py-2.5 text-xs font-semibold text-white shadow-md active:scale-95 transition-all"
                        @click="scrollToOptions"
                    >
                        Select Options
                    </button>
                </template>

                <template v-else>
                    <button
                        type="button"
                        class="w-1/2 rounded-xl border border-navyBlue bg-white px-3 py-2.5 text-xs font-semibold text-navyBlue hover:bg-zinc-50 active:scale-95 transition-all"
                        @click="triggerAddToCart(0)"
                    >
                        @lang('shop::app.products.view.add-to-cart')
                    </button>

                    <button
                        type="button"
                        class="w-1/2 rounded-xl bg-navyBlue px-3 py-2.5 text-xs font-semibold text-white shadow-md hover:bg-opacity-90 active:scale-95 transition-all"
                        @click="triggerAddToCart(1)"
                    >
                        @lang('shop::app.products.view.buy-now')
                    </button>
                </template>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-mobile-sticky-bar', {
            template: '#v-mobile-sticky-bar-template',

            props: {
                productId: Number,
                isSaleable: Boolean,
                priceHtml: String,
                productType: String,
            },

            data() {
                return {
                    isMounted: false,
                    isVisible: false,
                    requiresSelection: false,
                    observer: null,
                };
            },

            mounted() {
                this.isMounted = true;
                this.requiresSelection = (this.productType === 'configurable');

                this.$nextTick(() => {
                    this.initObserver();
                });

                this.$emitter.on('configurable-variant-selected-event', (variantId) => {
                    if (variantId && variantId > 0) {
                        this.requiresSelection = false;
                    } else if (this.productType === 'configurable') {
                        this.requiresSelection = true;
                    }
                });
            },

            methods: {
                initObserver() {
                    const sentinel = document.querySelector('#primary-pdp-cta-container');

                    if (! sentinel) {
                        return;
                    }

                    this.observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            // Sticky bar becomes visible when main CTA container is scrolled out of viewport
                            this.isVisible = ! entry.isIntersecting;
                        });
                    }, { threshold: 0.1 });

                    this.observer.observe(sentinel);
                },

                scrollToOptions() {
                    const optionsContainer = document.querySelector('#selected_configurable_option')?.parentElement || document.querySelector('#primary-pdp-cta-container');
                    if (optionsContainer) {
                        optionsContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                },

                triggerAddToCart(isBuyNow) {
                    const parentVue = this.$parent;

                    if (parentVue && typeof parentVue.addToCart === 'function') {
                        parentVue.is_buy_now = isBuyNow;
                        const form = parentVue.$refs.formData;
                        if (form) {
                            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                        }
                    }
                },
            },

            unmounted() {
                if (this.observer) {
                    this.observer.disconnect();
                }
            },
        });
    </script>
@endpushonce
