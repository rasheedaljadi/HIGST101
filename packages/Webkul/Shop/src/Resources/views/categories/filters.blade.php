{!! view_render_event('bagisto.shop.categories.view.filters.before') !!}

<!-- Desktop Filters Navigation -->
<div v-if="! isMobile">
    <!-- Filters Vue Component -->
    <v-filters
        @filter-applied="setFilters('filter', $event)"
        @filter-clear="clearFilters('filter', $event)"
    >
        <!-- Category Filter Shimmer Effect -->
        <x-shop::shimmer.categories.filters />
    </v-filters>
</div>

<!-- Mobile Filters Navigation -->
<div
    class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-zinc-200 bg-white px-5 ltr:left-0 rtl:right-0"
    v-if="isMobile"
>
    <!-- Filter Drawer -->
    <x-shop::drawer
        position="left"
        width="100%"
        ::is-active="isDrawerActive.filter"
    >
        <!-- Drawer Toggler -->
        <x-slot:toggle>
            <div
                class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-base font-medium uppercase max-md:py-3"
                @click="isDrawerActive.filter = true"
            >
                <span class="icon-filter-1 text-2xl"></span>

                @lang('shop::app.categories.filters.filter')
            </div>
        </x-slot>

        <!-- Drawer Header -->
        <x-slot:header>
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold">
                    @lang('shop::app.categories.filters.filters')
                </p>

                <p
                    class="cursor-pointer text-sm font-medium ltr:mr-[50px] rtl:ml-[50px]"
                    @click="clearFilters('filter', '')"
                >
                    @lang('shop::app.categories.filters.clear-all')
                </p>
            </div>
        </x-slot>

        <!-- Drawer Content -->
        <x-slot:content>
            <!-- Filters Vue Component -->
            <v-filters
                @filter-applied="setFilters('filter', $event)"
                @filter-clear="clearFilters('filter', $event)"
            >
                <!-- Category Filter Shimmer Effect -->
                <x-shop::shimmer.categories.filters />
            </v-filters>
        </x-slot>
    </x-shop::drawer>

    <!-- Separator -->
    <span class="h-5 w-0.5 bg-zinc-200"></span>

    <!-- Sort Drawer -->
    <x-shop::drawer
        position="bottom"
        width="100%"
        ::is-active="isDrawerActive.toolbar"
    >
        <!-- Drawer Toggler -->
        <x-slot:toggle>
            <div
                class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-base font-medium uppercase max-md:py-3"
                @click="isDrawerActive.toolbar = true"
            >
                <span class="icon-sort-1 text-2xl"></span>

                @lang('shop::app.categories.filters.sort')
            </div>
        </x-slot>

        <!-- Drawer Header -->
        <x-slot:header>
            <div class="flex items-center justify-between">
                <p class="text-lg font-semibold">
                    @lang('shop::app.categories.filters.sort')
                </p>
            </div>
        </x-slot>

        <!-- Drawer Content -->
        <x-slot:content class="!px-0">
            @include('shop::categories.toolbar')
        </x-slot>
    </x-shop::drawer>
</div>

{!! view_render_event('bagisto.shop.categories.view.filters.after') !!}

@pushOnce('scripts')
    <!-- Filters Vue template -->
    <script
        type="text/x-template"
        id="v-filters-template"
    >
        <!-- Filter Shimmer Effect -->
        <template v-if="isLoading">
            <x-shop::shimmer.categories.filters />
        </template>

        <!-- Filters Container -->
        <template v-else>
            <div class="panel-side journal-scroll grid max-h-[1320px] min-w-[342px] grid-cols-[1fr] overflow-y-auto overflow-x-hidden max-xl:min-w-[270px] md:max-w-[342px] md:ltr:pr-7 md:rtl:pl-7">
                <!-- Filters Header Container -->
                <div class="flex h-[50px] items-center justify-between border-b border-zinc-200 pb-2.5 max-md:hidden">
                    <p class="text-lg font-semibold max-sm:font-medium">
                        @lang('shop::app.categories.filters.filters')
                    </p>

                    <p
                        class="cursor-pointer text-xs font-medium"
                        tabindex="0"
                        @click="clear()"
                    >
                        @lang('shop::app.categories.filters.clear-all')
                    </p>
                </div>

                <!-- Context-Aware Category Filter Component -->
                <v-category-filter
                    ref="categoryFilterComponent"
                    :categories="categories"
                    :applied-category-ids="filters.applied['category_id'] || []"
                    :is-search-page="{{ (isset($isSearchPage) && $isSearchPage) || request()->routeIs('shop.search.index') || !isset($category) ? 'true' : 'false' }}"
                    :current-category-id="{{ isset($category) ? $category->id : 'null' }}"
                    :current-category-name="'{{ isset($category) ? addslashes($category->name) : '' }}'"
                    @values-applied="applyCategoryFilter($event)"
                >
                </v-category-filter>

                <!-- Permanent Price Filter Accordion -->
                <x-shop::accordion class="last:border-b-0">
                    <x-slot:header class="px-0 py-2.5 max-sm:!pb-1.5">
                        <div class="flex items-center justify-between">
                            <p class="text-lg font-semibold max-sm:text-base max-sm:font-medium">
                                {{ app()->getLocale() == 'ar' ? 'السعر' : 'Price' }}
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content class="!p-0">
                        <v-price-filter
                            :key="priceRefreshKey"
                            :default-price-range="filters.applied['price'] ? filters.applied['price'].join(',') : null"
                            @set-price-range="applyPriceFilter($event)"
                        >
                        </v-price-filter>
                    </x-slot>
                </x-shop::accordion>

                <!-- Dynamic Attribute Filters Items Vue Component -->
                <v-filter-item
                    ref="filterItemComponent"
                    :key="filterIndex"
                    :filter="filter"
                    v-for='(filter, filterIndex) in filters.available'
                    @values-applied="applyFilter(filter, $event)"
                >
                </v-filter-item>
            </div>
        </template>
    </script>

    <!-- Filter Item Vue template -->
    <script
        type="text/x-template"
        id="v-filter-item-template"
    >
        <x-shop::accordion class="last:border-b-0" v-if="filter.type === 'price' || (options && options.length) || isLoadingMore">
            <!-- Filter Item Header -->
            <x-slot:header class="px-0 py-2.5 max-sm:!pb-1.5">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-semibold max-sm:text-base max-sm:font-medium">
                        @{{ filter.name }}
                    </p>
                </div>
            </x-slot>

            <!-- Filter Item Content -->
            <x-slot:content class="!p-0">
                <!-- Price Range Filter -->
                <ul v-if="filter.type === 'price'">
                    <li>
                        <v-price-filter
                            :key="refreshKey"
                            :default-price-range="appliedValues"
                            @set-price-range="applyValue($event)"
                        >
                        </v-price-filter>
                    </li>
                </ul>

                <!-- Checkbox Filter Options -->
                <template v-else>
                    <!-- Search Box For Options -->
                    <div
                        class="flex flex-col gap-1"
                        v-if="filter.type !== 'boolean'"
                    >
                        <div class="relative">
                            <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>

                            <input
                                type="text"
                                class="block w-full rounded-xl border border-zinc-200 px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                                placeholder="@lang('shop::app.categories.filters.search.title')"
                                v-model="searchQuery"
                                v-debounce:500="searchOptions"
                            />
                        </div>

                        <p
                            class="mt-1 flex flex-row-reverse text-xs text-gray-600"
                            v-text="
                                '@lang('shop::app.categories.filters.search.results-info', ['currentCount' => 'currentCount', 'totalCount' => 'totalCount'])'
                                    .replace('currentCount', options.length)
                                    .replace('totalCount', meta.total)
                            "
                            v-if="meta && meta.total > 0"
                        >
                        </p>
                    </div>

                    <!-- Filter Options -->
                    <ul class="pb-3 text-base text-gray-700">
                        <template v-if="options.length">
                            <li
                                :key="`${filter.id}_${option.id}`"
                                v-for="(option, optionIndex) in options"
                            >
                                <div class="flex select-none items-center gap-x-4 rounded hover:bg-gray-100 max-sm:gap-x-1 max-sm:!p-0 ltr:pl-2 rtl:pr-2">
                                    <input
                                        type="checkbox"
                                        :id="`filter_${filter.id}_option_ ${option.id}`"
                                        class="peer hidden"
                                        :value="option.id"
                                        v-model="appliedValues"
                                        @change="applyValue"
                                    />

                                    <label
                                        class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue max-sm:text-xl"
                                        role="checkbox"
                                        aria-checked="false"
                                        :aria-label="option.name"
                                        :aria-labelledby="'label_option_' + option.id"
                                        tabindex="0"
                                        :for="`filter_${filter.id}_option_ ${option.id}`"
                                    >
                                    </label>

                                    <label
                                        class="w-full cursor-pointer p-2 text-base text-gray-900 max-sm:p-1 max-sm:text-sm ltr:pl-0 rtl:pr-0"
                                        :id="'label_option_' + option.id"
                                        :for="`filter_${filter.id}_option_ ${option.id}`"
                                        role="button"
                                        tabindex="0"
                                    >
                                        @{{ option.name }}
                                    </label>
                                </div>
                            </li>
                        </template>

                        <template v-else>
                            <li
                                class="flex flex-col items-center justify-center gap-2 py-2"
                                v-if="! isLoadingMore"
                            >
                                @lang('shop::app.categories.filters.search.no-options-available')
                            </li>

                            <div
                                class="mt-2"
                                v-else
                            >
                                <div class="flex flex-col items-center justify-between">
                                    <div class="shimmer h-5 w-[50%] self-end rounded"></div>
                                </div>

                                <div class="z-10 grid gap-1 rounded-lg bg-white">
                                    <div class="flex items-center gap-x-4 ltr:pl-2 rtl:pr-2">
                                        <div class="shimmer h-5 w-5 rounded"></div>

                                        <div class="p-2 ltr:pl-0 rtl:pr-0">
                                            <div class="shimmer h-5 w-[100px]"></div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-x-4 rounded ltr:pl-2 rtl:pr-2">
                                        <div class="shimmer h-5 w-5 rounded"></div>

                                        <div class="p-2 ltr:pl-0 rtl:pr-0">
                                            <div class="shimmer h-5 w-[100px]"></div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-x-4 rounded ltr:pl-2 rtl:pr-2">
                                        <div class="shimmer h-5 w-5 rounded"></div>

                                        <div class="p-2 ltr:pl-0 rtl:pr-0">
                                            <div class="shimmer h-5 w-[100px]"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </ul>

                    <!-- Load More Button -->
                    <div class="flex justify-center pb-3" v-if="meta && meta.current_page < meta.last_page">
                        <button
                            type="button"
                            class="rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="loadMoreOptions"
                            :disabled="isLoadingMore"
                        >
                            <span v-if="isLoadingMore">
                                @lang('shop::app.categories.filters.search.loading')
                            </span>

                            <span v-else>
                                @lang('shop::app.categories.filters.search.load-more')
                            </span>
                        </button>
                    </div>
                </template>
            </x-slot>
        </x-shop::accordion>
    </script>

    <!-- Category Filter Vue template -->
    <script
        type="text/x-template"
        id="v-category-filter-template"
    >
        <!-- 1. Search Page: Show Full Categories Filter (Level 1 Main + Level 2 Subcategories) -->
        <x-shop::accordion class="last:border-b-0" :is-active="true" v-if="isSearchPage && categories && categories.length">
            <!-- Filter Item Header -->
            <x-slot:header class="px-0 py-2.5 max-sm:!pb-1.5">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-semibold max-sm:text-base max-sm:font-medium">
                        {{ app()->getLocale() == 'ar' ? 'تصفية حسب الفئات' : 'Filter by Category' }}
                    </p>
                </div>
            </x-slot>

            <!-- Filter Item Content -->
            <x-slot:content class="!p-0 space-y-3 pb-3">
                <!-- Level 1: Main Category Dropdown -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ app()->getLocale() == 'ar' ? 'الفئة الرئيسية:' : 'Main Category:' }}
                    </label>
                    <div class="relative">
                        <select
                            v-model="selectedMainCategoryId"
                            @change="onMainCategoryChange"
                            class="block w-full rounded-xl border border-zinc-200 px-3.5 py-2.5 text-sm font-medium text-gray-900 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer shadow-xs"
                        >
                            <option value="">
                                {{ app()->getLocale() == 'ar' ? '— كل الفئات الرئيسية —' : '— All Main Categories —' }}
                            </option>
                            <option
                                v-for="cat in categories"
                                :key="'main_cat_' + cat.id"
                                :value="cat.id"
                            >
                                @{{ cat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Level 2: Subcategories (Visible when selected main category has children) -->
                <div
                    v-if="currentSubcategories && currentSubcategories.length"
                    class="space-y-1.5 pt-1"
                >
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ app()->getLocale() == 'ar' ? 'الفئة الفرعية:' : 'Subcategory:' }}
                    </label>
                    <div class="relative">
                        <select
                            v-model="selectedSubCategoryId"
                            @change="onSubCategoryChange"
                            class="block w-full rounded-xl border border-zinc-200 px-3.5 py-2.5 text-sm font-medium text-gray-900 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer shadow-xs"
                        >
                            <option value="">
                                {{ app()->getLocale() == 'ar' ? '— كل الفئات الفرعية التابعة —' : '— All Subcategories —' }}
                            </option>
                            <option
                                v-for="subCat in currentSubcategories"
                                :key="'sub_cat_' + subCat.id"
                                :value="subCat.id"
                            >
                                @{{ subCat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Clear Category Selection Button if active -->
                <div v-if="selectedMainCategoryId" class="flex items-center justify-between pt-1 text-xs">
                    <span class="text-gray-500">الفئة المحددة</span>
                    <button
                        type="button"
                        @click="clearCategorySelection"
                        class="text-red-500 hover:text-red-700 hover:underline cursor-pointer inline-flex items-center gap-1 font-medium"
                    >
                        <span>إلغاء التحديد</span>
                        <span>✕</span>
                    </button>
                </div>
            </x-slot>
        </x-shop::accordion>

        <!-- 2. Specific Category Page: Show only current category's subcategories (if any exist) -->
        <x-shop::accordion class="last:border-b-0" :is-active="true" v-else-if="!isSearchPage && subcategoriesOfCurrentCategory && subcategoriesOfCurrentCategory.length">
            <!-- Filter Item Header -->
            <x-slot:header class="px-0 py-2.5 max-sm:!pb-1.5">
                <div class="flex items-center justify-between">
                    <p class="text-lg font-semibold max-sm:text-base max-sm:font-medium">
                        {{ app()->getLocale() == 'ar' ? 'الأقسام الفرعية' : 'Subcategories' }}
                    </p>
                </div>
            </x-slot>

            <!-- Filter Item Content -->
            <x-slot:content class="!p-0 space-y-2 pb-3">
                <div class="relative">
                    <select
                        v-model="selectedDirectSubCategoryId"
                        @change="onDirectSubCategoryChange"
                        class="block w-full rounded-xl border border-zinc-200 px-3.5 py-2.5 text-sm font-medium text-gray-900 bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer shadow-xs"
                    >
                        <option value="">
                            {{ app()->getLocale() == 'ar' ? '— كل منتجات هذا القسم —' : '— All in this category —' }}
                        </option>
                        <option
                            v-for="sub in subcategoriesOfCurrentCategory"
                            :key="'sub_direct_' + sub.id"
                            :value="sub.id"
                        >
                            @{{ sub.name }}
                        </option>
                    </select>
                </div>

                <!-- Clear Direct Subcategory Selection if active -->
                <div v-if="selectedDirectSubCategoryId" class="flex items-center justify-between pt-1 text-xs">
                    <span class="text-gray-500">القسم المحدد</span>
                    <button
                        type="button"
                        @click="clearDirectSubCategorySelection"
                        class="text-red-500 hover:text-red-700 hover:underline cursor-pointer inline-flex items-center gap-1 font-medium"
                    >
                        <span>عرض كل القسم</span>
                        <span>✕</span>
                    </button>
                </div>
            </x-slot:content>
        </x-shop::accordion>
    </script>

    <script
        type="text/x-template"
        id="v-price-filter-template"
    >
        <div>
            <!-- Price Range Filter Shimmer -->
            <template v-if="isLoading">
                <x-shop::shimmer.range-slider />
            </template>

            <template v-else>
                <x-shop::range-slider
                    ::key="refreshKey"
                    default-type="price"
                    ::default-allowed-max-range="allowedMaxPrice"
                    ::default-min-range="minRange"
                    ::default-max-range="maxRange"
                    @change-range="setPriceRange($event)"
                />
            </template>
        </div>
    </script>

    <script type='module'>
        app.component('v-filters', {
            template: '#v-filters-template',

            data() {
                return {
                    isLoading: true,

                    priceRefreshKey: 0,

                    categories: [],

                    filters: {
                        available: {},

                        applied: {},
                    },
                };
            },

            mounted() {
                this.getCategories();

                this.getFilters();

                this.setFilters();
            },

            methods: {
                getCategories() {
                    this.$axios.get('{{ route("shop.api.categories.tree") }}', {
                            params: {
                                only_with_products: 1
                            }
                        })
                        .then((response) => {
                            this.categories = response.data.data || [];
                        })
                        .catch((error) => {
                            console.log('Error fetching category tree:', error);
                        });
                },

                getFilters() {
                    const queryParams = new URLSearchParams(window.location.search);
                    const selectedCatId = this.filters?.applied?.['category_id']?.[0] || queryParams.get('category_id') || "{{ isset($category) ? $category->id : '' }}";
                    const searchQuery = queryParams.get('query') || '';

                    this.$axios.get('{{ route("shop.api.categories.attributes") }}', {
                            params: {
                                category_id: selectedCatId,
                                query: searchQuery,
                            }
                        })
                        .then((response) => {
                            this.isLoading = false;

                            this.filters.available = response.data.data;
                        })
                        .catch((error) => {
                            console.log(error);
                        });
                },

                setFilters() {
                    let queryParams = new URLSearchParams(window.location.search);

                    queryParams.forEach((value, filter) => {
                        /**
                         * Removed all toolbar filters in order to prevent key duplication.
                         */
                        if (! ['sort', 'limit', 'mode'].includes(filter)) {
                            this.filters.applied[filter] = value.split(',');
                        }
                    });

                    this.$emit('filter-applied', this.filters.applied);
                },

                applyCategoryFilter(values) {
                    if (values && values.length) {
                        this.filters.applied['category_id'] = values;
                    } else {
                        delete this.filters.applied['category_id'];
                    }

                    this.getFilters();

                    this.$emit('filter-applied', this.filters.applied);
                },

                applyPriceFilter(values) {
                    if (values) {
                        this.filters.applied['price'] = [values];
                    } else {
                        delete this.filters.applied['price'];
                    }

                    this.$emit('filter-applied', this.filters.applied);
                },

                applyFilter(filter, values) {
                    if (values.length) {
                        this.filters.applied[filter.code] = values;
                    } else {
                        delete this.filters.applied[filter.code];
                    }

                    this.$emit('filter-applied', this.filters.applied);
                },

                clear() {
                    /**
                     * Clearing parent component.
                     */
                    this.filters.applied = {};

                    if (this.$refs.categoryFilterComponent) {
                        this.$refs.categoryFilterComponent.selectedMainCategoryId = '';
                        this.$refs.categoryFilterComponent.selectedSubCategoryId = '';
                    }

                    this.priceRefreshKey++;

                    this.getFilters();

                    this.$emit('filter-applied', this.filters.applied);
                },
            },
        });

        app.component('v-category-filter', {
            template: '#v-category-filter-template',

            props: {
                categories: {
                    type: Array,
                    default: () => [],
                },
                appliedCategoryIds: {
                    type: [Array, String],
                    default: () => [],
                },
                isSearchPage: {
                    type: Boolean,
                    default: true,
                },
                currentCategoryId: {
                    type: [Number, String],
                    default: null,
                },
                currentCategoryName: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    selectedMainCategoryId: '',
                    selectedSubCategoryId: '',
                    selectedDirectSubCategoryId: '',
                };
            },

            created() {
                this.syncApplied();
            },

            watch: {
                appliedCategoryIds: {
                    handler() {
                        this.syncApplied();
                    },
                    deep: true,
                },
                categories: {
                    handler() {
                        this.syncApplied();
                    },
                    deep: true,
                }
            },

            computed: {
                currentSubcategories() {
                    if (! this.selectedMainCategoryId) {
                        return [];
                    }

                    let mainCat = (this.categories || []).find(c => String(c.id) === String(this.selectedMainCategoryId));
                    return mainCat && mainCat.children ? mainCat.children : [];
                },

                subcategoriesOfCurrentCategory() {
                    if (this.isSearchPage || ! this.currentCategoryId) {
                        return [];
                    }

                    let current = this.findCategoryById(this.categories, this.currentCategoryId);
                    return current && current.children ? current.children : [];
                },
            },

            methods: {
                findCategoryById(cats, id) {
                    if (! cats || ! cats.length || ! id) {
                        return null;
                    }

                    for (let c of cats) {
                        if (String(c.id) === String(id)) {
                            return c;
                        }

                        if (c.children && c.children.length) {
                            let found = this.findCategoryById(c.children, id);
                            if (found) {
                                return found;
                            }
                        }
                    }

                    return null;
                },

                syncApplied() {
                    let applied = this.appliedCategoryIds || this.$parent?.$data?.filters?.applied?.['category_id'];
                    if (! applied || (Array.isArray(applied) && !applied.length)) {
                        this.selectedMainCategoryId = '';
                        this.selectedSubCategoryId = '';
                        this.selectedDirectSubCategoryId = '';
                        return;
                    }

                    let ids = (Array.isArray(applied) ? applied : String(applied).split(',')).map(v => String(v));
                    let currentId = ids[0];

                    if (! currentId) {
                        this.selectedMainCategoryId = '';
                        this.selectedSubCategoryId = '';
                        this.selectedDirectSubCategoryId = '';
                        return;
                    }

                    if (! this.isSearchPage && this.currentCategoryId) {
                        this.selectedDirectSubCategoryId = currentId;
                        return;
                    }

                    // Check if currentId is a Main category
                    let isMain = (this.categories || []).some(c => String(c.id) === currentId);
                    if (isMain) {
                        this.selectedMainCategoryId = currentId;
                        this.selectedSubCategoryId = '';
                        return;
                    }

                    // Check if currentId is a Subcategory
                    for (let cat of (this.categories || [])) {
                        if (cat.children && cat.children.some(child => String(child.id) === currentId)) {
                            this.selectedMainCategoryId = String(cat.id);
                            this.selectedSubCategoryId = currentId;
                            return;
                        }
                    }

                    this.selectedMainCategoryId = currentId;
                },

                onMainCategoryChange() {
                    this.selectedSubCategoryId = '';
                    if (this.selectedMainCategoryId) {
                        this.$emit('values-applied', [this.selectedMainCategoryId]);
                    } else {
                        this.$emit('values-applied', []);
                    }
                },

                onSubCategoryChange() {
                    if (this.selectedSubCategoryId) {
                        this.$emit('values-applied', [this.selectedSubCategoryId]);
                    } else if (this.selectedMainCategoryId) {
                        this.$emit('values-applied', [this.selectedMainCategoryId]);
                    } else {
                        this.$emit('values-applied', []);
                    }
                },

                onDirectSubCategoryChange() {
                    if (this.selectedDirectSubCategoryId) {
                        this.$emit('values-applied', [this.selectedDirectSubCategoryId]);
                    } else {
                        this.$emit('values-applied', []);
                    }
                },

                clearCategorySelection() {
                    this.selectedMainCategoryId = '';
                    this.selectedSubCategoryId = '';
                    this.$emit('values-applied', []);
                },

                clearDirectSubCategorySelection() {
                    this.selectedDirectSubCategoryId = '';
                    this.$emit('values-applied', []);
                },
            }
        });

        app.component('v-filter-item', {
            template: '#v-filter-item-template',

            props: ['filter'],

            data() {
                return {
                    options: [],

                    meta: null,

                    appliedValues: null,

                    currentPage: 1,

                    searchQuery: '',

                    isLoadingMore: true,

                    refreshKey: 0,
                }
            },

            created() {
                // Initialize values in created hook
                if (this.filter.code === 'price') {
                    this.appliedValues = this.$parent.$data.filters.applied[this.filter.code]?.join(',');
                } else {
                    this.appliedValues = this.$parent.$data.filters.applied[this.filter.code] ?? [];
                }
            },

            mounted() {
                this.fetchFilterOptions();
            },

            watch: {
                appliedValues: {
                    handler(newVal, oldVal) {
                        if (
                            this.filter.code === 'price' &&
                            newVal !== oldVal &&
                            !newVal
                        ) {
                            this.refreshKey++;
                        }
                    }
                }
            },


            methods: {
                applyValue($event) {
                    if (this.filter.code === 'price') {
                        this.appliedValues = $event;

                        this.$emit('values-applied', this.appliedValues);

                        return;
                    }

                    this.$emit('values-applied', this.appliedValues);
                },

                /**
                 * Search options based on query
                 */
                searchOptions() {
                    this.currentPage = 1;

                    this.fetchFilterOptions(true);
                },

                /**
                 * Load more options when "Load more" button is clicked
                 */
                loadMoreOptions() {
                    this.currentPage++;

                    this.fetchFilterOptions(false);
                },

                fetchFilterOptions(replace = true) {
                    this.isLoadingMore = true;

                    const url = `{{ route("shop.api.categories.attribute_options", 'attribute_id') }}`.replace('attribute_id', this.filter.id);

                    const queryParams = new URLSearchParams(window.location.search);
                    const categoryId = "{{ isset($category) ? $category->id : '' }}" || queryParams.get('category_id') || this.$parent?.$data?.filters?.applied?.['category_id']?.[0] || '';
                    const searchQuery = queryParams.get('query') || '';

                    this.$axios.get(url, {
                        params: {
                            page: this.currentPage,
                            search: this.searchQuery,
                            category_id: categoryId,
                            query: searchQuery,
                        }
                    })
                    .then(response => {
                        this.isLoadingMore = false;

                        this.options = replace
                            ? response.data.data
                            : [...this.options, ...response.data.data];

                        this.meta = response.data.meta;
                    })
                    .catch(error => {
                        this.isLoadingMore = false;
                    });
                },
            },
        });

        app.component('v-price-filter', {
            template: '#v-price-filter-template',

            props: ['defaultPriceRange'],

            data() {
                return {
                    refreshKey: 0,
                    isLoading: true,
                    allowedMaxPrice: 100,
                    priceRange: null,
                };
            },

            computed: {
                minRange() {
                    let priceRange = (this.priceRange || '0,100').split(',');
                    return priceRange[0];
                },

                maxRange() {
                    let priceRange = (this.priceRange || '0,100').split(',');
                    return priceRange[1];
                }
            },

            created() {
                // Initialize price range in created hook
                this.priceRange = this.defaultPriceRange ?? [0, 100].join(',');
            },

            mounted() {
                this.getMaxPrice();
            },

            methods: {
                getMaxPrice() {
                    this.$axios.get('{{ route("shop.api.categories.max_price", isset($category) && $category->id ? $category->id : null) }}')
                        .then((response) => {
                            this.isLoading = false;

                            /**
                             * If data is zero, then default price will be displayed.
                             */
                            if (response.data.data.max_price) {
                                this.allowedMaxPrice = response.data.data.max_price;
                            }

                            if (! this.defaultPriceRange) {
                                this.priceRange = [0, this.allowedMaxPrice].join(',');
                            }

                            ++this.refreshKey;
                        })
                        .catch((error) => {
                            console.log(error);
                        });
                },

                setPriceRange($event) {
                    this.priceRange = [$event.minRange, $event.maxRange].join(',');

                    this.$emit('set-price-range', this.priceRange);
                },
            },
        });
    </script>
@endpushonce
