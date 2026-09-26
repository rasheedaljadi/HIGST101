{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

@php
    $isHome = request()->routeIs('shop.home.index') && ! request()->has('query') && ! request()->has('new') && ! request()->has('sort');
    $isProducts = request()->routeIs('shop.search.index') || request()->routeIs('shop.product_or_category.index');
    $isDeals = request()->has('deal') || request()->fullUrlIs('*#flash-deals*');
@endphp

<div class="header-bottom-lockbox flex w-full items-center justify-between border-b border-slate-200/80 bg-white px-4 sm:px-8 lg:px-10 xl:px-14 select-none">
    
    <!-- Right Section (in RTL): Logo + Categories + Navigation -->
    <div class="flex items-center gap-4 xl:gap-6 shrink-0">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

        <!-- Logo -->
        <a
            href="{{ route('shop.home.index') }}"
            class="flex shrink-0 items-center select-none"
            aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
        >
            <img
                src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                width="132"
                height="34"
                class="h-8 xl:h-9 w-auto object-contain transition-transform hover:scale-[1.02]"
                alt="{{ config('app.name') }}"
            >
        </a>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

        <!-- "جميع الفئات" Drawer Toggler -->
        <v-desktop-category></v-desktop-category>

        <!-- Subtle Vertical Separator -->
        <span class="h-5 w-px bg-slate-200"></span>

        <!-- Main Navigation Links -->
        <nav class="flex items-center gap-4 xl:gap-6 text-sm font-semibold">
            <!-- الرئيسية -->
            <a 
                href="{{ route('shop.home.index') }}" 
                class="relative py-6 px-1 transition-colors {{ $isHome ? 'text-[#001A54] font-bold' : 'text-slate-600 hover:text-[#001A54]' }}"
            >
                الرئيسية
                @if ($isHome)
                    <span class="absolute bottom-0 left-0 right-0 h-[2.5px] rounded-full" style="background-color: #F5B800 !important;"></span>
                @endif
            </a>

            <!-- المنتجات -->
            <a 
                href="{{ route('shop.search.index') }}" 
                class="relative py-6 px-1 transition-colors {{ $isProducts ? 'text-[#001A54] font-bold' : 'text-slate-600 hover:text-[#001A54]' }}"
            >
                المنتجات
                @if ($isProducts)
                    <span class="absolute bottom-0 left-0 right-0 h-[2.5px] rounded-full" style="background-color: #F5B800 !important;"></span>
                @endif
            </a>

            <!-- العروض -->
            <a 
                href="{{ route('shop.home.index') }}#flash-deals" 
                class="relative py-6 px-1 transition-colors {{ $isDeals ? 'text-[#001A54] font-bold' : 'text-slate-600 hover:text-[#001A54]' }}"
            >
                العروض
                @if ($isDeals)
                    <span class="absolute bottom-0 left-0 right-0 h-[2.5px] rounded-full" style="background-color: #F5B800 !important;"></span>
                @endif
            </a>
        </nav>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}
    </div>

    <!-- Center Section: Prominent, Clearly Defined Search Bar -->
    <div class="flex-1 max-w-[500px] xl:max-w-[540px] mx-4 xl:mx-8">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

        <form
            action="{{ route('shop.search.index') }}"
            class="relative flex items-center w-full"
            role="search"
        >
            <label
                for="header-search-input"
                class="sr-only"
            >
                @lang('shop::app.components.layouts.header.desktop.bottom.search')
            </label>

            <!-- Prominent Search Container with crisp border and smooth focus styling -->
            <div class="flex items-center w-full h-[44px] bg-slate-50/80 hover:bg-slate-50 focus-within:bg-white border-2 border-slate-200 focus-within:border-[#001A54] rounded-2xl transition-all duration-200 pl-1.5 pr-4 shadow-[0_1px_3px_rgba(0,0,0,0.03)] focus-within:shadow-[0_4px_16px_rgba(0,26,84,0.08)]">
                <input
                    id="header-search-input"
                    type="text"
                    name="query"
                    value="{{ request('query') }}"
                    class="w-full bg-transparent py-2 text-sm text-slate-800 placeholder-slate-400 font-normal focus:outline-none"
                    minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                    maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                    placeholder="ابحث عن المنتجات، الماركات، والفئات..."
                    aria-label="ابحث عن المنتجات هنا ..."
                    pattern="[^\x5c]+"
                    required
                >

                <!-- Solid Deep Navy Search Button with High Visual Weight -->
                <button
                    type="submit"
                    class="shrink-0 w-9 h-9 rounded-xl highest-navy text-white flex items-center justify-center transition-all duration-150 shadow-sm hover:shadow hover:opacity-95 active:scale-95 cursor-pointer ml-1"
                    style="background-color: #001A54 !important; color: #ffffff !important;"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search')"
                >
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>

            @if (core()->getConfigData('catalog.products.settings.image_search'))
                @include('shop::search.images.index')
            @endif
        </form>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}
    </div>

    <!-- Left Section (in RTL): Balanced Shopping & Account Actions (De-cluttered, No Excess Dividers) -->
    <div class="flex items-center gap-5 xl:gap-6 shrink-0 select-none">
        
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

        <!-- 1. سلة التسوق (Shopping Cart) -->
        @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
            @include('shop::checkout.cart.mini-cart')
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

        <!-- 2. المقارنة (Compare) -->
        @if(core()->getConfigData('catalog.products.settings.compare_option'))
            <v-compare-header
                url="{{ route('shop.compare.index') }}"
                :is-customer="{{ auth()->guard('customer')->check() ? 'true' : 'false' }}"
                title="المقارنة"
            ></v-compare-header>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

        <!-- 3. المفضلة (Wishlist) -->
        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
            <v-wishlist-header
                url="{{ route('shop.customers.account.wishlist.index') }}"
                :is-customer="{{ auth()->guard('customer')->check() ? 'true' : 'false' }}"
                :initial-count="{{ auth()->guard('customer')->check() ? auth()->guard('customer')->user()->wishlist_items->count() : 0 }}"
                title="المفضلة"
            ></v-wishlist-header>
        @endif

        @auth('customer')
            <v-customer-notifications></v-customer-notifications>
        @endauth

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

        <!-- 4. حسابي (Account / Profile Dropdown) -->
        <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
            <!-- Trigger: Icon on top, Label "حسابي" below -->
            <x-slot:toggle>
                <div 
                    class="flex flex-col items-center justify-center cursor-pointer select-none group text-slate-700 hover:text-[#001A54] transition-colors"
                    role="button"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                    tabindex="0"
                >
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-700 group-hover:text-[#001A54] transition-colors" style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>

                    <span class="text-[11px] font-semibold text-slate-600 group-hover:text-[#001A54] transition-colors mt-0.5">
                        حسابي
                    </span>
                </div>
            </x-slot>

            <!-- Guest Dropdown -->
            @guest('customer')
                <x-slot:content class="w-[280px]">
                    <div class="grid gap-2">
                        <p class="text-lg font-bold text-gray-900">
                            @lang('shop::app.components.layouts.header.desktop.bottom.welcome-guest')
                        </p>

                        <p class="text-xs text-gray-500">
                            @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                        </p>
                    </div>

                    <p class="w-full my-3 border-t border-gray-100"></p>

                    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.before') !!}

                    <div class="flex gap-2.5">
                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                        <a
                            href="{{ route('shop.customer.session.create') }}"
                            class="flex-1 text-center primary-button text-xs py-2 rounded-xl"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                        </a>

                        <a
                            href="{{ route('shop.customers.register.index') }}"
                            class="flex-1 text-center secondary-button text-xs py-2 rounded-xl"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.sign-up')
                        </a>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_up_button.after') !!}
                    </div>

                    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.after') !!}
                </x-slot>
            @endguest

            <!-- Customers Dropdown -->
            @auth('customer')
                <x-slot:content class="!p-0 w-[240px]">
                    <div class="grid gap-1 p-4 pb-2">
                        <p class="text-base font-bold text-[#001A54]" v-pre>
                            @lang('shop::app.components.layouts.header.desktop.bottom.welcome')’
                            {{ auth()->guard('customer')->user()->first_name }}
                        </p>

                        <p class="text-xs text-gray-500">
                            @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                        </p>
                    </div>

                    <p class="w-full border-t border-gray-100"></p>

                    <div class="grid gap-0.5 py-1.5 text-sm">
                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.customers.account.profile.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                        </a>

                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.customers.account.orders.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.orders')
                        </a>

                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.customers.account.wishlist.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.wishlist')
                        </a>

                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.compare.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.compare')
                        </a>

                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.customers.account.reviews.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.reviews')
                        </a>

                        <a
                            class="px-4 py-2 hover:bg-gray-50 text-gray-700 hover:text-[#001A54] transition-colors"
                            href="{{ route('shop.customers.account.addresses.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.address')
                        </a>

                        <p class="w-full border-t border-gray-100 my-1"></p>

                        <!-- Logout -->
                        <x-shop::form
                            method="DELETE"
                            action="{{ route('shop.customer.session.destroy') }}"
                            id="customerLogout"
                        >
                        </x-shop::form>

                        <a
                            class="px-4 py-2 text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                            href="{{ route('shop.customer.session.destroy') }}"
                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                        </a>
                    </div>
                </x-slot>
            @endauth
        </x-shop::dropdown>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <!-- Nav Items shimmer: keep same height as real nav links -->
        <div
            class="flex items-center gap-2"
            style="height: 72px;"
            v-if="isLoading"
        >
            <span class="w-24 h-6 rounded shimmer" role="presentation"></span>
        </div>

        <!-- "جميع الفئات" Category Drawer Button -->
        <div class="flex items-center" v-else>
            <div
                class="flex h-full cursor-pointer items-center transition-colors group select-none"
                @click="toggleCategoryDrawer"
            >
                <span class="flex items-center gap-1.5 px-1 sm:px-2 text-sm font-bold highest-navy-text group-hover:text-blue-700 transition-colors" style="color: #001A54 !important;">
                    <svg class="w-4 h-4 shrink-0" style="color: #001A54 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                    جميع الفئات
                </span>
            </div>

            <!-- Category Drawer Integration -->
            <x-shop::drawer
                position="left"
                width="400px"
                ::is-active="isDrawerActive"
                @toggle="onDrawerToggle"
                @close="onDrawerClose"
            >
                <x-slot:toggle></x-slot>

                <x-slot:header class="border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between w-full">
                        <p class="text-xl font-bold text-[#001A54] dark:text-gray-100">
                            جميع الفئات
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!p-0 !overflow-y-auto !overflow-x-hidden">
                    <!-- Treeview Collapsible Accordion Container -->
                    <div class="w-full py-2">
                        <!-- Level 1 (Main Categories) -->
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="border-b border-gray-100 dark:border-gray-800 last:border-0"
                        >
                            <!-- Level 1 Category Row -->
                            <div
                                class="flex items-center justify-between px-5 py-3.5 transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer select-none group"
                                @click="category.children && category.children.length ? toggleExpand(category.id) : visitCategory(category.url)"
                            >
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors flex-1">
                                    @{{ category.name }}
                                </span>

                                <!-- Toggle Button / Arrow for Level 1 -->
                                <div
                                    v-if="category.children && category.children.length"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 px-2 py-0.5 rounded-full font-medium">
                                        @{{ category.children.length }}
                                    </span>
                                    <span
                                        class="text-base font-bold icon-arrow-right rtl:icon-arrow-left inline-block transition-transform duration-200 text-gray-400 group-hover:text-blue-600"
                                        :class="{'rotate-90 rtl:-rotate-90 text-blue-600 dark:text-blue-400': isExpanded(category.id)}"
                                    ></span>
                                </div>
                            </div>

                            <!-- Level 2 Collapsible Section (Accordion) -->
                            <div
                                v-if="category.children && category.children.length && isExpanded(category.id)"
                                class="bg-gray-50/80 dark:bg-gray-900/60 border-t border-gray-100 dark:border-gray-800/60"
                            >
                                <!-- Direct Link to Parent Category -->
                                <a
                                    :href="category.url"
                                    class="flex items-center justify-between pr-8 pl-5 rtl:pr-8 rtl:pl-5 py-2.5 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline border-b border-gray-100/60 dark:border-gray-800/40 bg-blue-50/40"
                                >
                                    <span>عرض كافة منتجات @{{ category.name }}</span>
                                    <span class="text-sm icon-arrow-right rtl:icon-arrow-left"></span>
                                </a>

                                <div
                                    v-for="secondLevelCategory in category.children"
                                    :key="secondLevelCategory.id"
                                    class="border-b border-gray-100/60 dark:border-gray-800/40 last:border-0"
                                >
                                    <!-- Level 2 Category Row -->
                                    <div
                                        class="flex items-center justify-between pr-8 pl-5 rtl:pr-8 rtl:pl-5 py-3 transition-colors duration-200 hover:bg-gray-100/70 dark:hover:bg-gray-800/80 cursor-pointer select-none group"
                                        @click="secondLevelCategory.children && secondLevelCategory.children.length ? toggleExpand(secondLevelCategory.id) : visitCategory(secondLevelCategory.url)"
                                    >
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors flex-1">
                                            @{{ secondLevelCategory.name }}
                                        </span>

                                        <!-- Toggle Button / Arrow for Level 2 -->
                                        <div
                                            v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                            class="flex items-center gap-2"
                                        >
                                            <span class="text-xs bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-300 px-1.5 py-0.5 rounded-full font-medium">
                                                @{{ secondLevelCategory.children.length }}
                                            </span>
                                            <span
                                                class="text-sm icon-arrow-right rtl:icon-arrow-left inline-block transition-transform duration-200 text-gray-400 group-hover:text-blue-600"
                                                :class="{'rotate-90 rtl:-rotate-90 text-blue-600 dark:text-blue-400': isExpanded(secondLevelCategory.id)}"
                                            ></span>
                                        </div>
                                    </div>

                                    <!-- Level 3 Collapsible Section (Accordion) -->
                                    <div
                                        v-if="secondLevelCategory.children && secondLevelCategory.children.length && isExpanded(secondLevelCategory.id)"
                                        class="bg-gray-100/70 dark:bg-gray-900/80 border-t border-gray-100 dark:border-gray-800"
                                    >
                                        <!-- Direct Link to Level 2 Category -->
                                        <a
                                            :href="secondLevelCategory.url"
                                            class="flex items-center justify-between pr-12 pl-5 rtl:pr-12 rtl:pl-5 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline border-b border-gray-200/40"
                                        >
                                            <span>عرض كافة منتجات @{{ secondLevelCategory.name }}</span>
                                            <span class="text-xs icon-arrow-right rtl:icon-arrow-left"></span>
                                        </a>

                                        <div
                                            v-for="thirdLevelCategory in secondLevelCategory.children"
                                            :key="thirdLevelCategory.id"
                                            class="border-b border-gray-200/40 dark:border-gray-800/40 last:border-0"
                                        >
                                            <a
                                                :href="thirdLevelCategory.url"
                                                class="flex items-center justify-between pr-12 pl-5 rtl:pr-12 rtl:pl-5 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 hover:bg-gray-200/50 transition-colors"
                                            >
                                                @{{ thirdLevelCategory.name }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot>
            </x-shop::drawer>
        </div>
    </script>

    <script type="module">
        app.component('v-desktop-category', {
            template: '#v-desktop-category-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    isDrawerActive: false,
                    expandedCategories: {},
                }
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories_tree_v5');

                        if (stored) {
                            this.categories = JSON.parse(stored);
                            this.isLoading = false;
                        }
                    } catch (e) {}

                    this.getCategories();
                },

                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                            try {
                                localStorage.setItem('categories_tree_v5', JSON.stringify(this.categories));
                            } catch (e) {}
                        })
                        .catch(error => {
                            this.isLoading = false;
                            console.log(error);
                        });
                },

                toggleExpand(categoryId) {
                    this.expandedCategories = {
                        ...this.expandedCategories,
                        [categoryId]: !this.expandedCategories[categoryId]
                    };
                },

                isExpanded(categoryId) {
                    return !!this.expandedCategories[categoryId];
                },

                visitCategory(url) {
                    if (url) {
                        window.location.href = url;
                    }
                },

                toggleCategoryDrawer() {
                    this.isDrawerActive = !this.isDrawerActive;
                },

                onDrawerToggle(event) {
                    this.isDrawerActive = event.isActive;
                },

                onDrawerClose() {
                    this.isDrawerActive = false;
                },
            },
        });
    </script>

    @auth('customer')
        <script type="text/x-template" id="v-customer-notifications-template">
            <div class="relative">
                <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                    <x-slot:toggle>
                        <div class="flex flex-col items-center justify-center cursor-pointer select-none group text-slate-700 hover:text-[#001A54] transition-colors" role="button" aria-label="الإشعارات" tabindex="0">
                            <div class="relative inline-flex items-center justify-center">
                                <span class="inline-block text-xl cursor-pointer icon-notification text-slate-700 group-hover:text-[#001A54] transition-colors" role="presentation"></span>
                                <span v-if="totalUnread > 0" class="absolute -top-1.5 -right-2 min-w-[17px] h-[17px] px-1 flex items-center justify-center rounded-full bg-[#F5B800] text-black text-[10px] font-bold leading-none shadow-sm ring-1 ring-white select-none pointer-events-none z-10">
                                    @{{ totalUnread > 9 ? '9+' : totalUnread }}
                                </span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-600 group-hover:text-[#001A54] transition-colors mt-0.5">
                                الإشعارات
                            </span>
                        </div>
                    </x-slot>

                    <x-slot:content class="!p-0 w-[360px]">
                        <div class="flex items-center justify-between border-b border-zinc-200 p-4">
                            <h4 class="text-base font-semibold text-zinc-900">الإشعارات</h4>
                            <button v-if="notifications.length && totalUnread > 0" @click="markAllAsRead" class="text-xs text-navyBlue hover:underline cursor-pointer">تعليم الكل كمقروء</button>
                        </div>

                        <div v-if="isLoading" class="p-4 text-center">
                            <span class="shimmer block h-12 rounded"></span>
                        </div>

                        <div v-else-if="!notifications.length" class="p-6 text-center text-sm text-zinc-500">
                            لا توجد إشعارات حالياً
                        </div>

                        <div v-else class="max-h-[320px] overflow-y-auto divide-y divide-zinc-100">
                            <div v-for="item in notifications" :key="item.id" @click="handleItemClick(item)" class="p-3 transition-colors cursor-pointer hover:bg-zinc-50" :class="{'bg-blue-50/40': !item.read}">
                                <p class="text-xs font-semibold text-zinc-900">@{{ item.title || 'إشعار' }}</p>
                                <p class="text-xs text-zinc-600 line-clamp-2 mt-0.5">@{{ item.message }}</p>
                            </div>
                        </div>

                        <div class="border-t border-zinc-200 p-3 text-center">
                            <a href="{{ route('shop.customers.account.notifications.index') }}" class="text-xs font-medium text-navyBlue hover:underline">عرض جميع الإشعارات</a>
                        </div>
                    </x-slot:content>
                </x-shop::dropdown>
            </div>
        </script>

        <script type="module">
            app.component('v-customer-notifications', {
                template: '#v-customer-notifications-template',
                data() {
                    return {
                        isLoading: true,
                        notifications: [],
                        totalUnread: 0,
                    }
                },
                mounted() {
                    this.getNotifications();
                },
                methods: {
                    getNotifications() {
                        this.$axios.get("{{ route('shop.customers.account.notifications.get', ['limit' => 5]) }}")
                            .then(response => {
                                this.isLoading = false;
                                this.notifications = response.data.notifications.data || [];
                                this.totalUnread = response.data.total_unread || 0;
                            })
                            .catch(() => { this.isLoading = false; });
                    },
                    handleItemClick(item) {
                        if (!item.read) {
                            this.$axios.post(`{{ url('customer/account/notifications/mark-as-read') }}/${item.id}`)
                                .then(res => { window.location.href = res.data.redirect_url; })
                                .catch(() => { window.location.href = item.action_url || "{{ route('shop.customers.account.notifications.index') }}"; });
                        } else {
                            window.location.href = item.action_url || "{{ route('shop.customers.account.notifications.index') }}";
                        }
                    },
                    markAllAsRead() {
                        this.$axios.post("{{ route('shop.customers.account.notifications.mark_all_as_read') }}")
                            .then(() => {
                                this.totalUnread = 0;
                                this.notifications.forEach(n => n.read = 1);
                            });
                    }
                }
            });
        </script>
    @endauth
@endpushonce

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}
