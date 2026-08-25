@props([
    'cardStyle' => 'standard',
])

<v-product-card
    {{ $attributes }}
    :product="product"
    :card-style="{{ json_encode($cardStyle) }}"
>
</v-product-card>

@pushonce('scripts')
    <script
        type="text/x-template"
        id="v-product-card-template"
    >
        <div v-if="product" class="w-full h-full">
            <!-- Modern Flash Deal Style Product Card (Default Grid & Carousel View) -->
            <div
                v-if="mode != 'list'"
                class="w-full h-full min-h-[400px] max-h-[420px] bg-white dark:bg-gray-900 rounded-2xl sm:rounded-3xl p-3 shadow-sm hover:shadow-md transition-all relative border border-gray-100 dark:border-gray-800 flex flex-col justify-between overflow-hidden box-border select-none"
            >
                <!-- Product Image Container with Golden Frame (Restored Standard Fixed Frame) -->
                <div 
                    class="relative w-full aspect-[336/302] rounded-xl sm:rounded-2xl overflow-hidden bg-white dark:bg-gray-800 shrink-0 mb-2 flex items-center justify-center"
                    style="aspect-ratio: 336 / 302; border: 2px solid #D4AF37;"
                >
                    <!-- Badges Overlay (Supports Multiple Badges: Featured, Discount %, New) -->
                    <div class="absolute top-2.5 right-2.5 z-10 flex flex-col gap-1 items-end pointer-events-none">
                        <!-- Featured Badge -->
                        <span 
                            v-if="product.is_featured"
                            class="bg-[#060C3B] text-white font-bold px-2 sm:px-2.5 py-0.5 sm:py-1 text-xs shadow-sm flex items-center justify-center rounded-md"
                            style="background-color: #060C3B !important; color: #ffffff !important;"
                        >
                            @lang('shop::app.components.products.card.featured')
                        </span>

                        <!-- Sale / Discount Percentage Badge -->
                        <span 
                            v-if="product.on_sale"
                            class="bg-[#e60023] text-white font-bold px-2 sm:px-2.5 py-0.5 sm:py-1 text-xs shadow-sm flex items-center justify-center rounded-md"
                            style="background-color: #e60023 !important; color: #ffffff !important;"
                        >
                            <template v-if="product.discount_percent && product.discount_percent > 0">
                                -@{{ product.discount_percent }}%
                            </template>
                            <template v-else>
                                @lang('shop::app.components.products.card.sale')
                            </template>
                        </span>

                        <!-- New Badge -->
                        <span 
                            v-if="product.is_new && !product.is_featured && !product.on_sale"
                            class="bg-[#060C3B] text-white font-bold px-2 sm:px-2.5 py-0.5 sm:py-1 text-xs shadow-sm flex items-center justify-center rounded-md"
                        >
                            @lang('shop::app.components.products.card.new')
                        </span>
                    </div>

                    <!-- Wishlist & Compare Buttons Overlay -->
                    <div class="absolute top-2.5 left-2.5 z-10 flex flex-col gap-1.5 opacity-90 transition-opacity group-hover:opacity-100">
                        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                            <button
                                class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/90 dark:bg-gray-800/90 shadow-sm flex items-center justify-center text-gray-700 dark:text-gray-200 hover:text-red-500 transition-colors"
                                role="button"
                                aria-label="@lang('shop::app.components.products.card.add-to-wishlist')"
                                @click.stop="addToWishlist()"
                            >
                                <span :class="product.is_wishlist ? 'icon-heart-fill text-red-500 text-sm sm:text-base' : 'icon-heart text-sm sm:text-base'"></span>
                            </button>
                        @endif

                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                            <button
                                class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/90 dark:bg-gray-800/90 shadow-sm flex items-center justify-center text-gray-700 dark:text-gray-200 hover:text-blue-500 transition-colors"
                                role="button"
                                aria-label="@lang('shop::app.components.products.card.add-to-compare')"
                                @click.stop="addToCompare(product.id)"
                            >
                                <span class="icon-compare text-sm sm:text-base"></span>
                            </button>
                        @endif
                    </div>

                    <!-- Product Image -->
                    <a 
                        :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)" 
                        class="w-full h-full flex items-center justify-center overflow-hidden block"
                    >
                        <img 
                            :src="product.base_image?.medium_image_url || product.base_image?.small_image_url || '{{ bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}'" 
                            :alt="product.name"
                            class="w-full h-full group-hover:scale-105 transition-transform duration-300 block"
                            style="object-fit: fill !important; width: 100% !important; height: 100% !important; transform: scale(0.60, 1.80) !important; transform-origin: center !important;"
                            loading="lazy"
                            v-on:error="$event.target.src = '{{ bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}'"
                        />
                    </a>
                </div>

                <!-- Product Title Area -->
                <a 
                    :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)" 
                    class="block flex items-center my-1 px-0.5 shrink-0 overflow-hidden"
                >
                    <h3 
                        class="text-xs sm:text-sm font-bold text-[#001A54] dark:text-gray-100 hover:opacity-80 transition-opacity text-right rtl:text-right ltr:text-left w-full leading-tight sm:leading-snug" 
                        :title="product.name"
                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; max-height: 2.6rem;"
                    >
                        @{{ product.name }}
                    </h3>
                </a>

                <!-- Bottom Footer Area: Price & Cart Button -->
                <div class="border-t border-gray-100 dark:border-gray-800 pt-2 mt-auto flex items-center justify-between gap-2 shrink-0">
                    <!-- Price Section -->
                    <div 
                        class="flex flex-col items-start justify-center overflow-hidden text-xs sm:text-sm md:text-base font-bold text-[#001A54] dark:text-white"
                        v-html="product.price_html"
                    >
                    </div>

                    <!-- Cart Button -->
                    <button
                        class="bg-[#060C3B] hover:opacity-90 text-white font-bold p-2 sm:p-2.5 rounded-full flex items-center justify-center shadow-md shrink-0 w-9 h-9 sm:w-10 sm:h-10 transition-transform active:scale-95"
                        style="background-color: #060C3B !important; color: #ffffff !important;"
                        :disabled="! product.is_saleable || isAddingToCart"
                        @click="addToCart()"
                        title="أضف للسلة"
                        aria-label="أضف للسلة"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="color: #ffffff !important;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- List Card View -->
            <div
                v-else
                class="relative flex w-full gap-4 overflow-hidden rounded-2xl border border-gray-100 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 shadow-sm max-sm:flex-wrap"
            >
                <div 
                    class="group relative w-[180px] sm:w-[220px] aspect-[336/302] overflow-hidden rounded-xl bg-white dark:bg-gray-800 shrink-0 flex items-center justify-center"
                    style="aspect-ratio: 336 / 302; border: 2px solid #D4AF37;"
                >
                    <a :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)" class="w-full h-full flex items-center justify-center block">
                        <img 
                            :src="product.base_image?.medium_image_url || product.base_image?.small_image_url || '{{ bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}'" 
                            :alt="product.name"
                            class="w-full h-full group-hover:scale-105 transition-transform duration-300 block"
                            style="object-fit: fill !important; width: 100% !important; height: 100% !important; transform: scale(0.60, 1.80) !important; transform-origin: center !important;"
                            loading="lazy"
                            v-on:error="$event.target.src = '{{ bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}'"
                        />
                    </a>
                </div>

                <div class="flex flex-col justify-between flex-1 py-1 min-w-0">
                    <div>
                        <a :href="'{{ route('shop.product_or_category.index', ':slug') }}'.replace(':slug', product.url_key)">
                            <h3 class="text-base sm:text-lg font-bold text-[#001A54] dark:text-gray-100 hover:opacity-80 transition-opacity mb-2">
                                @{{ product.name }}
                            </h3>
                        </a>
                        
                        <div class="text-base font-bold text-[#001A54] dark:text-white mb-3" v-html="product.price_html"></div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            class="bg-[#060C3B] hover:opacity-90 text-white font-bold px-4 py-2 rounded-xl flex items-center gap-2 shadow-md transition-transform active:scale-95 text-sm"
                            style="background-color: #060C3B !important; color: #ffffff !important;"
                            :disabled="! product.is_saleable || isAddingToCart"
                            @click="addToCart()"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>@lang('shop::app.components.products.card.add-to-cart')</span>
                        </button>

                        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                            <button
                                class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:text-red-500 dark:border-gray-700"
                                @click="addToWishlist()"
                            >
                                <span :class="product.is_wishlist ? 'icon-heart-fill text-red-500 text-xl' : 'icon-heart text-xl'"></span>
                            </button>
                        @endif

                        @if (core()->getConfigData('catalog.products.settings.compare_option'))
                            <button
                                class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:text-blue-500 dark:border-gray-700"
                                @click="addToCompare(product.id)"
                            >
                                <span class="icon-compare text-xl"></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-card', {
            template: '#v-product-card-template',

            props: ['mode', 'product', 'cardStyle'],

            data() {
                return {
                    isCustomer: '{{ auth()->guard('customer')->check() }}',

                    isAddingToCart: false,
                }
            },

            methods: {
                addToWishlist() {
                    if (this.isCustomer) {
                        this.$axios.post(`{{ route('shop.api.customers.account.wishlist.store') }}`, {
                                product_id: this.product.id
                            })
                            .then(response => {
                                this.product.is_wishlist = ! this.product.is_wishlist;

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                            })
                            .catch(error => {});
                        } else {
                            window.location.href = "{{ route('shop.customer.session.index')}}";
                        }
                },

                addToCompare(productId) {
                    /**
                     * This will handle for customers.
                     */
                    if (this.isCustomer) {
                        this.$axios.post('{{ route("shop.api.compare.store") }}', {
                                'product_id': productId
                            })
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.data.message });
                            })
                            .catch(error => {
                                if ([400, 422].includes(error.response.status)) {
                                    this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.data.message });

                                    return;
                                }

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message});
                            });

                        return;
                    }

                    /**
                     * This will handle for guests.
                     */
                    let items = this.getStorageValue() ?? [];

                    if (items.length) {
                        if (! items.includes(productId)) {
                            items.push(productId);

                            localStorage.setItem('compare_items', JSON.stringify(items));

                            this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.components.products.card.add-to-compare-success')" });
                        } else {
                            this.$emitter.emit('add-flash', { type: 'warning', message: "@lang('shop::app.components.products.card.already-in-compare')" });
                        }
                    } else {
                        localStorage.setItem('compare_items', JSON.stringify([productId]));

                        this.$emitter.emit('add-flash', { type: 'success', message: "@lang('shop::app.components.products.card.add-to-compare-success')" });

                    }
                },

                getStorageValue(key) {
                    let value = localStorage.getItem('compare_items');

                    if (! value) {
                        return [];
                    }

                    return JSON.parse(value);
                },

                getImageDistortionStyle(productId) {
                    // Force height enlargement (16:9 vertical ratio factor = ~1.77) across all cards
                    return 'object-fit: cover !important; object-position: center !important; width: 100% !important; height: 100% !important; transform: scaleY(1.78) !important; transform-origin: center !important;';
                },

                addToCart() {
                    this.isAddingToCart = true;

                    this.$axios.post('{{ route("shop.api.checkout.cart.store") }}', {
                            'quantity': 1,
                            'product_id': this.product.id,
                        })
                        .then(response => {
                            if (response.data.message) {
                                this.$emitter.emit('update-mini-cart', response.data.data );

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                            } else {
                                this.$emitter.emit('add-flash', { type: 'warning', message: response.data.data.message });
                            }

                            this.isAddingToCart = false;
                        })
                        .catch(error => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });

                            if (error.response.data.redirect_uri) {
                                window.location.href = error.response.data.redirect_uri;
                            }

                            this.isAddingToCart = false;
                        });
                },
            },
        });
    </script>
@endpushonce
