<style>
    .highest-navy { background-color: #001A54 !important; }
    .highest-navy-text { color: #001A54 !important; }
    .highest-navy-border { border-color: #001A54 !important; }
    .highest-yellow { background-color: #F5B800 !important; }
    .highest-yellow-text { color: #F5B800 !important; }
    .highest-yellow-border { border-color: #F5B800 !important; }
    .highest-yellow-badge {
        background-color: #F5B800 !important;
        color: #000000 !important;
    }
    /* ── Header stability: prevent layout shift on nav clicks ── */
    .header-bottom-lockbox {
        height: 72px;
        min-height: 72px;
        max-height: 72px;
        overflow: hidden;
        /* Promote to GPU layer so inner repaints don't trigger outer reflows */
        will-change: transform;
        transform: translateZ(0);
    }
    /* Prevent flash-of-shimmer after Vue mounts */
    .v-cloak-shimmer-desktop,
    .v-cloak-shimmer-mobile {
        display: none !important;
    }
    /* Keep header sticky during transitions — no size changes */
    header {
        contain: layout style;
    }
</style>

<header class="sticky top-0 z-[999] bg-white shadow-md max-lg:shadow-none">
    <div class="max-lg:hidden">
        <x-shop::layouts.header.desktop.top />
    </div>

    <v-header-switcher>
        <!-- Desktop Header Shimmer: locked to exact 72px height so swap to real header is seamless -->
        <div class="flex flex-wrap max-lg:hidden">
            <div class="header-bottom-lockbox flex w-full items-center justify-between border-b border-gray-200/90 bg-white px-4 sm:px-8 lg:px-10 xl:px-14">
                <!-- Right Navigation Section (Logo + Nav Shimmer) -->
                <div class="flex items-center gap-4">
                    <span class="shimmer block h-8 w-28 rounded" role="presentation"></span>
                    <span class="shimmer block h-6 w-20 rounded" role="presentation"></span>
                    <div class="h-5 w-px bg-gray-200 mx-1"></div>
                    <div class="flex items-center gap-4">
                        <span class="shimmer h-6 w-16 rounded" role="presentation"></span>
                        <span class="shimmer h-6 w-16 rounded" role="presentation"></span>
                        <span class="shimmer h-6 w-16 rounded" role="presentation"></span>
                    </div>
                </div>

                <!-- Search Bar Shimmer -->
                <div class="flex-1 max-w-[500px] mx-4">
                    <span class="shimmer block h-10 w-full rounded-xl" role="presentation"></span>
                </div>

                <!-- Shopping Actions Shimmer -->
                <div class="flex items-center gap-4">
                    <span class="shimmer block h-10 w-12 rounded" role="presentation"></span>
                    <span class="shimmer block h-10 w-12 rounded" role="presentation"></span>
                    <span class="shimmer block h-10 w-12 rounded" role="presentation"></span>
                    <span class="shimmer block h-10 w-12 rounded" role="presentation"></span>
                </div>
            </div>
        </div>


        <!-- Mobile Header Shimmer -->
        <div class="flex flex-wrap gap-4 px-4 pb-4 pt-6 shadow-sm lg:hidden">
            <div class="flex w-full items-center justify-between">
                <!-- Left Navigation -->
                <div class="flex items-center gap-x-1.5">
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <span 
                        class="shimmer block h-[29px] w-[131px] rounded" 
                        role="presentation"
                    >
                    </span>
                </div>

                <!-- Right Navigation Icons -->
                <div class="flex items-center gap-x-5 max-md:gap-x-4">
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                </div>
            </div>

            <!-- Search Bar Shimmer -->
            <div class="flex w-full items-center">
                <div class="relative w-full">
                    <span
                        class="shimmer block h-[42px] w-full rounded-xl px-11 py-3.5 max-md:rounded-lg"
                        role="presentation"
                    >
                    </span>
                </div>
            </div>
        </div>
    </v-header-switcher>
</header>

{!! view_render_event('bagisto.shop.layout.header.after') !!}

@pushOnce('scripts')
    <script 
        type="text/x-template" 
        id="v-header-switcher-template"
    >
        <v-desktop-header v-if="isDesktop"></v-desktop-header>
        
        <v-mobile-header v-else></v-mobile-header>
    </script>

    <script type="module">
        app.component('v-header-switcher', {
            template: '#v-header-switcher-template',

            data() {
                return {
                    isDesktop: window.innerWidth >= 1024
                }
            },

            mounted() {
                this.media = window.matchMedia('(min-width: 1024px)');

                this.media.addEventListener('change', this.handleMedia);
            },

            beforeUnmount() {
                this.media.removeEventListener('change', this.handleMedia);
            },

            methods: {
                handleMedia(e) {
                    this.isDesktop = e.matches;
                }
            }
        });

        app.component('v-desktop-header', {
            template: '#v-desktop-header-template'
        });

        app.component('v-mobile-header', {
            template: '#v-mobile-header-template'
        });

        app.component('v-wishlist-header', {
            template: '#v-wishlist-header-template',

            props: {
                url: {
                    type: String,
                    required: true,
                },
                isCustomer: {
                    type: Boolean,
                    default: false,
                },
                initialCount: {
                    type: Number,
                    default: 0,
                },
                title: {
                    type: String,
                    default: 'المفضلة',
                },
            },

            data() {
                return {
                    count: parseInt(this.initialCount) || 0,
                };
            },

            mounted() {
                if (this.isCustomer) {
                    this.fetchWishlistCount();

                    if (this.$emitter) {
                        this.$emitter.on('wishlist-updated', () => {
                            this.fetchWishlistCount();
                        });

                        this.$emitter.on('add-flash', () => {
                            setTimeout(() => {
                                this.fetchWishlistCount();
                            }, 600);
                        });
                    }
                }
            },

            methods: {
                fetchWishlistCount() {
                    this.$axios.get("{{ route('shop.api.customers.account.wishlist.index') }}")
                        .then(response => {
                            if (response.data && response.data.data) {
                                this.count = response.data.data.length;
                            }
                        })
                        .catch(() => {});
                },
            },
        });

        app.component('v-compare-header', {
            template: '#v-compare-header-template',

            props: {
                url: {
                    type: String,
                    required: true,
                },
                isCustomer: {
                    type: Boolean,
                    default: false,
                },
                title: {
                    type: String,
                    default: 'المقارنة',
                },
            },

            data() {
                return {
                    count: 0,
                };
            },

            mounted() {
                this.updateCount();

                if (this.$emitter) {
                    this.$emitter.on('compare-updated', () => {
                        this.updateCount();
                    });

                    this.$emitter.on('add-flash', () => {
                        setTimeout(() => {
                            this.updateCount();
                        }, 500);
                    });
                }
            },

            methods: {
                updateCount() {
                    if (this.isCustomer) {
                        this.$axios.get("{{ route('shop.api.compare.index') }}")
                            .then(response => {
                                if (response.data && response.data.data) {
                                    this.count = response.data.data.length;
                                }
                            })
                            .catch(() => {});
                    } else {
                        try {
                            const items = JSON.parse(localStorage.getItem('compare_items') || '[]');
                            this.count = Array.isArray(items) ? items.length : 0;
                        } catch (e) {
                            this.count = 0;
                        }
                    }
                },
            },
        });
    </script>

    <script 
        type="text/x-template" 
        id="v-wishlist-header-template"
    >
        <a
            :href="url"
            class="flex flex-col items-center justify-center cursor-pointer select-none group text-slate-700 hover:text-red-500 transition-colors"
            :aria-label="title"
            :title="title"
        >
            <div class="relative inline-flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-700 group-hover:text-red-500 transition-colors" style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                </svg>

                <span
                    v-if="isCustomer && count > 0"
                    class="absolute -top-1.5 -right-2 min-w-[17px] h-[17px] px-1 flex items-center justify-center rounded-full text-black text-[10px] font-bold leading-none shadow-sm ring-1 ring-white select-none pointer-events-none z-10 highest-yellow-badge"
                    style="background-color: #F5B800 !important; color: #000000 !important;"
                >
                    @{{ count > 9 ? '9+' : count }}
                </span>
            </div>

            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-red-500 transition-colors max-lg:hidden mt-0.5">
                @{{ title }}
            </span>
        </a>
    </script>

    <script 
        type="text/x-template" 
        id="v-compare-header-template"
    >
        <a
            :href="url"
            class="flex flex-col items-center justify-center cursor-pointer select-none group text-slate-700 hover:text-[#001A54] transition-colors"
            :aria-label="title"
            :title="title"
        >
            <div class="relative inline-flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-700 group-hover:text-[#001A54] transition-colors" style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                    <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                    <path d="M7 21h10"/>
                    <path d="M12 3v18"/>
                    <path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>
                </svg>

                <span
                    class="absolute -top-1.5 -right-2 min-w-[17px] h-[17px] px-1 flex items-center justify-center rounded-full text-black text-[10px] font-bold leading-none shadow-sm ring-1 ring-white select-none pointer-events-none z-10 highest-yellow-badge"
                    style="background-color: #F5B800 !important; color: #000000 !important;"
                >
                    @{{ count > 9 ? '9+' : count }}
                </span>
            </div>

            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-[#001A54] transition-colors max-lg:hidden mt-0.5">
                @{{ title }}
            </span>
        </a>
    </script>

    <script 
        type="text/x-template" 
        id="v-desktop-header-template"
    >
        <x-shop::layouts.header.desktop />
    </script>

    <script 
        type="text/x-template" 
        id="v-mobile-header-template"
    >
        <x-shop::layouts.header.mobile />
    </script>
@endpushonce
