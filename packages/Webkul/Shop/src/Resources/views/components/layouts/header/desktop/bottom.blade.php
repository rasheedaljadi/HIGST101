{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

<div class="flex min-h-[78px] w-full justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] max-1180:px-8">
    <!--
        This section will provide categories for the first, second, and third levels. If
        additional levels are required, users can customize them according to their needs.
    -->
    <!-- Left Nagivation Section -->
    <div class="flex items-center gap-x-10 max-[1180px]:gap-x-5">
        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

        <a
            href="{{ route('shop.home.index') }}"
            aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
        >
            <img
                src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                width="131"
                height="29"
                alt="{{ config('app.name') }}"
            >
        </a>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

        <!-- Header Left Navigation: "الكل" Categories Drawer + الرئيسية, المنتجات, العروض -->
        <div class="flex items-center gap-x-2 sm:gap-x-4">
            <!-- "الكل" Categories Toggler -->
            <v-desktop-category></v-desktop-category>

            <!-- 3 Main Navigation Links -->
            <nav class="flex items-center gap-3 sm:gap-5 text-base font-bold text-[#001A54] dark:text-gray-100 border-s border-gray-200 dark:border-gray-700 ps-3 sm:ps-4">
                <a 
                    href="{{ route('shop.home.index') }}" 
                    class="hover:opacity-80 transition-opacity py-2 px-2 sm:px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    الرئيسية
                </a>

                <a 
                    href="{{ route('shop.search.index') }}" 
                    class="hover:opacity-80 transition-opacity py-2 px-2 sm:px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    المنتجات
                </a>

                <a 
                    href="{{ route('shop.home.index') }}#flash-deals" 
                    class="hover:opacity-80 transition-opacity py-2 px-2 sm:px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    العروض
                </a>
            </nav>
        </div>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}
    </div>

    <!-- Right Nagivation Section -->
    <div class="flex items-center gap-x-9 max-[1100px]:gap-x-6 max-lg:gap-x-8">

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

        <!-- Search Bar Container -->
        <div class="relative w-full">
            <form
                action="{{ route('shop.search.index') }}"
                class="flex max-w-[445px] items-center"
                role="search"
            >
                <label
                    for="organic-search"
                    class="sr-only"
                >
                    @lang('shop::app.components.layouts.header.desktop.bottom.search')
                </label>

                <div class="icon-search pointer-events-none absolute top-2.5 flex items-center text-xl ltr:left-3 rtl:right-3"></div>

                <input
                    type="text"
                    name="query"
                    value="{{ request('query') }}"
                    class="block w-full py-3 text-xs font-medium text-gray-900 transition-all border border-transparent rounded-lg bg-zinc-100 px-11 hover:border-gray-400 focus:border-gray-400"
                    minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                    maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                    placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-required="true"
                    pattern="[^\x5c]+"
                    required
                >

                <button
                    type="submit"
                    class="hidden"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.submit')"
                >
                </button>

                @if (core()->getConfigData('catalog.products.settings.image_search'))
                    @include('shop::search.images.index')
                @endif
            </form>
        </div>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}

        <!-- Right Navigation Links -->
        <div class="mt-1.5 flex gap-x-8 max-[1100px]:gap-x-6 max-lg:gap-x-8">

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

            <!-- Compare -->
            @if(core()->getConfigData('catalog.products.settings.compare_option'))
                <a
                    href="{{ route('shop.compare.index') }}"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.compare')"
                >
                    <span
                        class="inline-block text-2xl cursor-pointer icon-compare"
                        role="presentation"
                    ></span>
                </a>
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

            <!-- Mini cart -->
            @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
                @include('shop::checkout.cart.mini-cart')
            @endif

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

            @auth('customer')
                <v-customer-notifications></v-customer-notifications>
            @endauth

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

            <!-- user profile -->
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <span
                        class="inline-block text-2xl cursor-pointer icon-users"
                        role="button"
                        aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                        tabindex="0"
                    ></span>
                </x-slot>

                <!-- Guest Dropdown -->
                @guest('customer')
                    <x-slot:content>
                        <div class="grid gap-2.5">
                            <p class="text-xl font-dmserif">
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome-guest')
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border border-zinc-200"></p>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.before') !!}

                        <div class="flex gap-4 mt-6">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                            <a
                                href="{{ route('shop.customer.session.create') }}"
                                class="block m-0 mx-auto text-base text-center primary-button w-max rounded-2xl px-7 max-md:rounded-lg ltr:ml-0 rtl:mr-0"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="block m-0 mx-auto text-base text-center border-2 secondary-button w-max rounded-2xl px-7 max-md:rounded-lg max-md:py-3 ltr:ml-0 rtl:mr-0"
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
                    <x-slot:content class="!p-0">
                        <div class="grid gap-2.5 p-5 pb-0">
                            <p class="text-xl font-dmserif" v-pre>
                                @lang('shop::app.components.layouts.header.desktop.bottom.welcome')’
                                {{ auth()->guard('customer')->user()->first_name }}
                            </p>

                            <p class="text-sm">
                                @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                            </p>
                        </div>

                        <p class="w-full mt-3 border border-zinc-200"></p>

                        <div class="mt-2.5 grid gap-1 pb-2.5">
                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.before') !!}

                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customers.account.profile.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                            </a>

                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customers.account.orders.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.orders')
                            </a>

                            @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                                <a
                                    class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                    href="{{ route('shop.customers.account.wishlist.index') }}"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.wishlist')
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
                                    class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                    href="{{ route('shop.customer.session.destroy') }}"
                                    onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                >
                                    @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                                </a>
                            @endauth

                            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.after') !!}
                        </div>
                    </x-slot>
                @endauth
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <!-- Loading State -->
        <div
            class="flex items-center gap-5"
            v-if="isLoading"
        >
            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>

            <span
                class="w-20 h-6 rounded shimmer"
                role="presentation"
            ></span>
        </div>

        <!-- "الكل" Category Drawer Layout -->
        <div class="flex items-center" v-else>
            <!-- "All" button for opening the category drawer -->
            <div
                class="flex h-[77px] cursor-pointer items-center border-b-4 border-transparent hover:border-b-4 hover:border-[#001A54] transition-colors"
                @click="toggleCategoryDrawer"
            >
                <span class="flex items-center gap-1.5 px-2.5 sm:px-3 text-base font-bold text-[#001A54] dark:text-gray-100 hover:opacity-80 transition-opacity">
                    <span class="text-xl icon-hamburger"></span>

                    @lang('shop::app.components.layouts.header.desktop.bottom.categories')
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
                            @lang('shop::app.components.layouts.header.desktop.bottom.categories')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!px-0">
                    <!-- Wrapper with transition effects -->
                    <div class="relative h-full overflow-hidden">
                        <!-- Sliding container with 3 levels -->
                        <div
                            class="flex h-full transition-transform duration-300 ease-in-out"
                            :class="{
                                'ltr:translate-x-0 rtl:translate-x-0': currentViewLevel === 'main',
                                'ltr:-translate-x-full rtl:translate-x-full': currentViewLevel === 'second',
                                'ltr:-translate-x-[200%] rtl:translate-x-[200%]': currentViewLevel === 'third'
                            }"
                        >
                            <!-- 1. First Level View (Only Main Top-Level Categories) -->
                            <div class="h-[calc(100vh-74px)] w-full flex-shrink-0 overflow-auto">
                                <div class="py-2">
                                    <div
                                        v-for="category in categories"
                                        :key="category.id"
                                        class="border-b border-gray-100 dark:border-gray-800 last:border-0"
                                    >
                                        <!-- Category with Children: Click navigates to Level 2 -->
                                        <div
                                            v-if="category.children && category.children.length"
                                            class="flex items-center justify-between px-6 py-3.5 transition-colors duration-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 group"
                                            @click="showSecondLevel(category)"
                                        >
                                            <span class="text-base font-semibold text-gray-900 dark:text-gray-100 group-hover:text-blue-600 transition-colors">
                                                @{{ category.name }}
                                            </span>

                                            <span class="flex items-center gap-1.5 text-xs text-gray-400">
                                                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-medium">@{{ category.children.length }}</span>
                                                <span class="text-lg icon-arrow-right rtl:icon-arrow-left text-gray-400 group-hover:text-blue-600 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-all"></span>
                                            </span>
                                        </div>

                                        <!-- Direct Link Category without Children -->
                                        <a
                                            v-else
                                            :href="category.url"
                                            class="flex items-center justify-between px-6 py-3.5 text-base font-semibold text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 transition-colors"
                                        >
                                            @{{ category.name }}
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Second Level View -->
                            <div class="h-[calc(100vh-74px)] w-full flex-shrink-0 overflow-auto">
                                <div v-if="currentFirstLevelCategory">
                                    <!-- Back Button Header -->
                                    <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between sticky top-0 z-10">
                                        <button
                                            @click="goBackToMainView"
                                            class="flex items-center gap-2 text-sm font-bold text-[#001A54] dark:text-blue-400 hover:opacity-80 transition-opacity focus:outline-none"
                                        >
                                            <span class="text-base icon-arrow-left rtl:icon-arrow-right"></span>
                                            <span>@lang('shop::app.components.layouts.header.desktop.bottom.back-button')</span>
                                        </button>

                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 truncate max-w-[160px]">
                                            @{{ currentFirstLevelCategory.name }}
                                        </span>
                                    </div>

                                    <!-- Link to View All Products in Parent Category -->
                                    <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-blue-50/50 dark:bg-gray-800/40">
                                        <a
                                            :href="currentFirstLevelCategory.url"
                                            class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center justify-between"
                                        >
                                            <span>عرض كافة منتجات @{{ currentFirstLevelCategory.name }}</span>
                                            <span class="text-base icon-arrow-right rtl:icon-arrow-left"></span>
                                        </a>
                                    </div>

                                    <!-- Second Level Items List -->
                                    <div class="py-2">
                                        <div
                                            v-for="secondLevelCategory in currentFirstLevelCategory.children"
                                            :key="secondLevelCategory.id"
                                            class="border-b border-gray-100 dark:border-gray-800 last:border-0"
                                        >
                                            <!-- If has Level 3 children: clicking navigates to Level 3 -->
                                            <div
                                                v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                                class="flex items-center justify-between px-6 py-3.5 transition-colors duration-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 group"
                                                @click="showThirdLevel(secondLevelCategory)"
                                            >
                                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 transition-colors">
                                                    @{{ secondLevelCategory.name }}
                                                </span>

                                                <span class="flex items-center gap-1.5 text-xs text-gray-400">
                                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-medium">@{{ secondLevelCategory.children.length }}</span>
                                                    <span class="text-base icon-arrow-right rtl:icon-arrow-left text-gray-400 group-hover:text-blue-600 group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-all"></span>
                                                </span>
                                            </div>

                                            <!-- Direct Link to Second Level Category -->
                                            <a
                                                v-else
                                                :href="secondLevelCategory.url"
                                                class="flex items-center justify-between px-6 py-3.5 text-sm font-medium text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 transition-colors"
                                            >
                                                @{{ secondLevelCategory.name }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Third Level View -->
                            <div class="h-[calc(100vh-74px)] w-full flex-shrink-0 overflow-auto">
                                <div v-if="currentSecondLevelCategory">
                                    <!-- Back Button Header -->
                                    <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between sticky top-0 z-10">
                                        <button
                                            @click="goBackToSecondView"
                                            class="flex items-center gap-2 text-sm font-bold text-[#001A54] dark:text-blue-400 hover:opacity-80 transition-opacity focus:outline-none"
                                        >
                                            <span class="text-base icon-arrow-left rtl:icon-arrow-right"></span>
                                            <span>رجوع</span>
                                        </button>

                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 truncate max-w-[160px]">
                                            @{{ currentSecondLevelCategory.name }}
                                        </span>
                                    </div>

                                    <!-- Link to View All Products in Second Level Category -->
                                    <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-blue-50/50 dark:bg-gray-800/40">
                                        <a
                                            :href="currentSecondLevelCategory.url"
                                            class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center justify-between"
                                        >
                                            <span>عرض كافة منتجات @{{ currentSecondLevelCategory.name }}</span>
                                            <span class="text-base icon-arrow-right rtl:icon-arrow-left"></span>
                                        </a>
                                    </div>

                                    <!-- Third Level Items List -->
                                    <div class="py-2">
                                        <div
                                            v-for="thirdLevelCategory in currentSecondLevelCategory.children"
                                            :key="thirdLevelCategory.id"
                                            class="border-b border-gray-100 dark:border-gray-800 last:border-0"
                                        >
                                            <a
                                                :href="thirdLevelCategory.url"
                                                class="flex items-center justify-between px-6 py-3.5 text-sm font-medium text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-blue-600 transition-colors"
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
                    currentViewLevel: 'main',
                    currentFirstLevelCategory: null,
                    currentSecondLevelCategory: null,
                }
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories_tree_v3');

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
                                localStorage.setItem('categories_tree_v3', JSON.stringify(this.categories));
                            } catch (e) {}
                        })
                        .catch(error => {
                            this.isLoading = false;
                            console.log(error);
                        });
                },

                pairCategoryChildren(category) {
                    if (! category.children) return [];

                    return category.children.reduce((result, value, index, array) => {
                        if (index % 2 === 0) {
                            result.push(array.slice(index, index + 2));
                        }
                        return result;
                    }, []);
                },

                toggleCategoryDrawer() {
                    this.isDrawerActive = !this.isDrawerActive;
                    if (this.isDrawerActive) {
                        this.currentViewLevel = 'main';
                        this.currentFirstLevelCategory = null;
                        this.currentSecondLevelCategory = null;
                    }
                },

                onDrawerToggle(event) {
                    this.isDrawerActive = event.isActive;
                    if (!this.isDrawerActive) {
                        this.currentViewLevel = 'main';
                    }
                },

                onDrawerClose() {
                    this.isDrawerActive = false;
                    this.currentViewLevel = 'main';
                    this.currentFirstLevelCategory = null;
                    this.currentSecondLevelCategory = null;
                },

                showSecondLevel(category) {
                    this.currentFirstLevelCategory = category;
                    this.currentViewLevel = 'second';
                },

                showThirdLevel(secondLevelCategory) {
                    this.currentSecondLevelCategory = secondLevelCategory;
                    this.currentViewLevel = 'third';
                },

                goBackToMainView() {
                    this.currentViewLevel = 'main';
                    this.currentSecondLevelCategory = null;
                },

                goBackToSecondView() {
                    this.currentViewLevel = 'second';
                    this.currentSecondLevelCategory = null;
                }
            },
        });
    </script>

    @auth('customer')
        <script type="text/x-template" id="v-customer-notifications-template">
            <div class="relative">
                <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                    <x-slot:toggle>
                        <div class="relative cursor-pointer flex items-center">
                            <span class="inline-block text-2xl cursor-pointer icon-notification text-navyBlue dark:text-white" role="button" aria-label="الإشعارات" tabindex="0"></span>
                            <span v-if="totalUnread > 0" class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
                                @{{ totalUnread > 9 ? '9+' : totalUnread }}
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
