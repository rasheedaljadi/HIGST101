<!--
    This code needs to be refactored to reduce the amount of PHP in the Blade
    template as much as possible.
-->
@php
    $showCompare = (bool) core()->getConfigData('catalog.products.settings.compare_option');

    $showWishlist = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');
@endphp

<div class="flex flex-wrap gap-4 px-4 pt-6 pb-4 shadow-sm lg:hidden">
    <div class="flex items-center justify-between w-full">
        <!-- Left Navigation -->
        <div class="flex items-center gap-x-1.5">
            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.before') !!}

            <!-- Drawer -->
            <v-mobile-drawer></v-mobile-drawer>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.before') !!}

            <a
                href="{{ route('shop.home.index') }}"
                class="max-h-[30px]"
                aria-label="@lang('shop::app.components.layouts.header.mobile.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>

            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.logo.after') !!}
        </div>

        <!-- Right Navigation -->
        <div>
            <div class="flex items-center gap-x-5 max-md:gap-x-4">
                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.before') !!}

                @if($showCompare)
                    <a
                        href="{{ route('shop.compare.index') }}"
                        aria-label="@lang('shop::app.components.layouts.header.mobile.compare')"
                    >
                        <span class="text-2xl cursor-pointer icon-compare"></span>
                    </a>
                @endif

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.compare.after') !!}

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.before') !!}

                @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                    @include('shop::checkout.cart.mini-cart')
                @endif

                {!! view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.after') !!}

                @auth('customer')
                    <v-customer-notifications></v-customer-notifications>
                @endauth

                <!-- For Large screens -->
                <div class="max-md:hidden">
                    <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                        <x-slot:toggle>
                            <span class="text-2xl cursor-pointer icon-users"></span>
                            </x-slot>

                            <!-- Guest Dropdown -->
                            @guest('customer')
                                <x-slot:content>
                                    <div class="grid gap-2.5">
                                        <p class="text-xl font-dmserif">
                                            @lang('shop::app.components.layouts.header.mobile.welcome-guest')
                                        </p>

                                        <p class="text-sm">
                                            @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                        </p>
                                    </div>

                                    <p class="w-full mt-3 border border-zinc-200"></p>

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.before') !!}

                                    <div class="flex gap-4 mt-6">
                                        {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.before') !!}

                                    <a
                                        href="{{ route('shop.customer.session.create') }}"
                                        class="block py-4 m-0 mx-auto text-base font-medium text-center text-white cursor-pointer w-max rounded-2xl bg-navyBlue px-7 ltr:ml-0 rtl:mr-0"
                                    >
                                            @lang('shop::app.components.layouts.header.mobile.sign-in')
                                        </a>

                                    <a
                                        href="{{ route('shop.customers.register.index') }}"
                                        class="m-0 mx-auto block w-max cursor-pointer rounded-2xl border-2 border-navyBlue bg-white px-7 py-3.5 text-center text-base font-medium text-navyBlue ltr:ml-0 rtl:mr-0"
                                    >
                                            @lang('shop::app.components.layouts.header.mobile.sign-up')
                                        </a>

                                        {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.after') !!}
                                    </div>

                                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.after') !!}
                                    </x-slot>
                            @endguest

                                <!-- Customers Dropdown -->
                                @auth('customer')
                                    <x-slot:content class="!p-0">
                                        <div class="grid gap-2.5 p-5 pb-0">
                                            <p class="text-xl font-dmserif" v-pre>
                                        @lang('shop::app.components.layouts.header.mobile.welcome')’
                                                {{ auth()->guard('customer')->user()->first_name }}
                                            </p>

                                            <p class="text-sm">
                                                @lang('shop::app.components.layouts.header.mobile.dropdown-text')
                                            </p>
                                        </div>

                                        <p class="w-full mt-3 border border-zinc-200"></p>

                                        <div class="mt-2.5 grid gap-1 pb-2.5">
                                            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.before') !!}

                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="{{ route('shop.customers.account.profile.index') }}"
                                    >
                                                @lang('shop::app.components.layouts.header.mobile.profile')
                                            </a>

                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="{{ route('shop.customers.account.orders.index') }}"
                                    >
                                                @lang('shop::app.components.layouts.header.mobile.orders')
                                            </a>

                                            @if ($showWishlist)
                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                            href="{{ route('shop.customers.account.wishlist.index') }}"
                                        >
                                                    @lang('shop::app.components.layouts.header.mobile.wishlist')
                                                </a>
                                            @endif

                                            <!--Customers logout-->
                                            @auth('customer')
                                        <x-shop::form
                                            method="DELETE"
                                            action="{{ route('shop.customer.session.destroy') }}"
                                            id="customerLogout"
                                        />

                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                                    href="{{ route('shop.customer.session.destroy') }}"
                                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                        >
                                                    @lang('shop::app.components.layouts.header.mobile.logout')
                                                </a>
                                            @endauth

                                            {!! view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.after') !!}
                                        </div>
                                        </x-slot>
                                @endauth
                    </x-shop::dropdown>
                </div>

                <!-- For Medium and small screen -->
                <div class="md:hidden">
                    @guest('customer')
                        <a
                            href="{{ route('shop.customer.session.create') }}"
                            aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                        >
                            <span class="text-2xl cursor-pointer icon-users"></span>
                        </a>
                    @endguest

                    <!-- Customers Dropdown -->
                    @auth('customer')
                        <a
                            href="{{ route('shop.customers.account.index') }}"
                            aria-label="@lang('shop::app.components.layouts.header.mobile.account')"
                        >
                            <span class="text-2xl cursor-pointer icon-users"></span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.before') !!}

    <!-- Serach Catalog Form -->
    <form action="{{ route('shop.search.index') }}" class="flex items-center w-full">
        <label
            for="organic-search"
            class="sr-only"
        >
            @lang('shop::app.components.layouts.header.mobile.search')
        </label>

        <div class="relative w-full">
            <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>

            <input
                type="text"
                class="block w-full rounded-xl border border-['#E3E3E3'] px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                name="query"
                value="{{ request('query') }}"
                placeholder="@lang('shop::app.components.layouts.header.mobile.search-text')"
                required
            >

            @if (core()->getConfigData('catalog.products.settings.image_search'))
                @include('shop::search.images.index')
            @endif
        </div>
    </form>

    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.search.after') !!}
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-mobile-drawer-template">
            <x-shop::drawer
                position="left"
                width="100%"
                @close="onDrawerClose"
            >
                <x-slot:toggle>
                    <span class="text-2xl cursor-pointer icon-hamburger"></span>
                </x-slot>

                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <a href="{{ route('shop.home.index') }}">
                            <img
                                src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                                alt="{{ config('app.name') }}"
                                width="131"
                                height="29"
                            >
                        </a>
                    </div>
                </x-slot>

                <x-slot:content class="!p-0">
                    <!-- Account Profile Hero Section -->
                    <div class="p-4 border-b border-zinc-200">
                        <div class="grid grid-cols-[auto_1fr] items-center gap-4 rounded-xl border border-zinc-200 p-2.5">
                            <div>
                                <img
                                src="{{ auth()->user()?->image_url ??  bagisto_asset('images/user-placeholder.png') }}"
                                    class="h-[60px] w-[60px] rounded-full max-md:rounded-full"
                                >
                            </div>

                            @guest('customer')
                                <a
                                    href="{{ route('shop.customer.session.create') }}"
                                    class="flex text-base font-medium"
                                >
                                    @lang('shop::app.components.layouts.header.mobile.login')

                                    <i class="icon-double-arrow text-2xl ltr:ml-2.5 rtl:mr-2.5"></i>
                                </a>
                            @endguest

                            @auth('customer')
                                <div
                                    class="flex flex-col justify-between gap-2.5 max-md:gap-0"
                                    v-pre
                                >
                                    <p class="text-2xl break-all font-mediums max-md:text-xl">Hello! {{ auth()->user()?->first_name }}</p>

                                    <p class="no-underline text-zinc-500 max-md:text-sm">{{ auth()->user()?->email }}</p>
                                </div>
                            @endauth
                        </div>
                    </div>

                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.before') !!}

                    <!-- Main Navigation Links: الرئيسية, المنتجات, العروض -->
                    <div class="flex flex-col border-b border-zinc-200 py-2 bg-gray-50/50">
                        <a 
                            href="{{ route('shop.home.index') }}"
                            class="flex items-center gap-3 px-5 py-3 text-base font-bold text-[#001A54] hover:bg-gray-100"
                        >
                            الرئيسية
                        </a>
                        <a 
                            href="{{ route('shop.search.index') }}"
                            class="flex items-center gap-3 px-5 py-3 text-base font-bold text-[#001A54] hover:bg-gray-100"
                        >
                            المنتجات
                        </a>
                        <a 
                            href="{{ route('shop.home.index') }}#flash-deals"
                            class="flex items-center gap-3 px-5 py-3 text-base font-bold text-[#001A54] hover:bg-gray-100"
                        >
                            العروض
                        </a>
                    </div>

                    <!-- Mobile category view -->
                    <v-mobile-category ref="mobileCategory"></v-mobile-category>

                    {!! view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.after') !!}
                </x-slot>

                <x-slot:footer>
                    <!-- Localization & Currency Section -->
                @if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 )
                                    <div class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-zinc-200 bg-white px-5 ltr:left-0 rtl:right-0">
                                        <!-- Filter Drawer -->
                                        <x-shop::drawer
                                            position="bottom"
                                            width="100%"
                                        >
                                            <!-- Drawer Toggler -->
                                            <x-slot:toggle>
                                                <div
                                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                                    role="button"
                                                    v-pre
                                                >
                                                    {{ core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode() }}
                                                </div>
                                            </x-slot>

                                            <!-- Drawer Header -->
                                            <x-slot:header>
                                                <div class="flex items-center justify-between">
                                                    <p class="text-lg font-semibold">
                                                        @lang('shop::app.components.layouts.header.mobile.currencies')
                                                    </p>
                                                </div>
                                            </x-slot>

                                            <!-- Drawer Content -->
                                            <x-slot:content class="!px-0">
                                                <div
                                                    class="overflow-auto"
                                                    :style="{ height: getCurrentScreenHeight }"
                                                >
                                                    <v-currency-switcher></v-currency-switcher>
                                                </div>
                                            </x-slot>
                                        </x-shop::drawer>

                                        <!-- Seperator -->
                                        <span class="h-5 w-0.5 bg-zinc-200"></span>

                                        <!-- Sort Drawer -->
                                        <x-shop::drawer
                                            position="bottom"
                                            width="100%"
                                        >
                                            <!-- Drawer Toggler -->
                                            <x-slot:toggle>
                                                <div
                                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                                    role="button"
                                                    v-pre
                                                >
                                                    <img
                                        src="{{ ! empty(core()->getCurrentLocale()->logo_url)
                        ? core()->getCurrentLocale()->logo_url
                        : bagisto_asset('images/default-language.svg')
                                                            }}"
                                                        class="h-full"
                                                        alt="Default locale"
                                                        width="24"
                                                        height="16"
                                                    />

                                                    {{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}
                                                </div>
                                            </x-slot>

                                            <!-- Drawer Header -->
                                            <x-slot:header>
                                                <div class="flex items-center justify-between">
                                                    <p class="text-lg font-semibold">
                                                        @lang('shop::app.components.layouts.header.mobile.locales')
                                                    </p>
                                                </div>
                                            </x-slot>

                                            <!-- Drawer Content -->
                                            <x-slot:content class="!px-0">
                                                <div
                                                    class="overflow-auto"
                                                    :style="{ height: getCurrentScreenHeight }"
                                                >
                                                    <v-locale-switcher></v-locale-switcher>
                                                </div>
                                            </x-slot>
                                        </x-shop::drawer>
                                    </div>
                    @endif
                </x-slot>
            </x-shop::drawer>
        </script>

    <script type="text/x-template" id="v-mobile-category-template">
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
    </script>

    <script type="module">
        app.component('v-mobile-category', {
            template: '#v-mobile-category-template',

            data() {
                return  {
                    categories: [],
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
                            this.categories = response.data.data;
                            try {
                                localStorage.setItem('categories_tree_v5', JSON.stringify(this.categories));
                            } catch (e) {}
                        })
                        .catch(error => {
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
                }
            },
        });

        app.component('v-mobile-drawer', {
            template: '#v-mobile-drawer-template',

            methods: {
                onDrawerClose() {
                }
            },
        });
    </script>
@endpushonce
