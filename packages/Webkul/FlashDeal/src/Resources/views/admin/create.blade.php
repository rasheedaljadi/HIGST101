<x-admin::layouts>
    <x-slot:title>
        إنشاء عرض سريع جديد
    </x-slot>

    <x-admin::form :action="route('admin.marketing.promotions.flash_deals.store')">
        <!-- Page Header -->
        <div class="flex items-center justify-between gap-4 border-b dark:border-gray-800 pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="text-amber-500">⚡</span>
                    <span>إنشاء عرض سريع جديد</span>
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    حدد توقيت العرض، أضف المنتجات، واضبط أسعار الخصم والكميات المخصصة
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.marketing.promotions.flash_deals.index') }}"
                    class="transparent-button border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold py-2.5 px-5 rounded-xl transition-all"
                >
                    إلغاء
                </a>

                <button
                    type="submit"
                    class="primary-button bg-amber-500 hover:bg-amber-600 text-gray-950 font-black py-2.5 px-6 rounded-xl shadow-md transition-all flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>حفظ العرض السريع</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left Side: Deal Timing & Main Info (4 cols) -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <h2 class="text-base font-bold mb-4 text-gray-900 dark:text-white flex items-center gap-2 border-b dark:border-gray-800 pb-3">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>إعدادات العرض والسير</span>
                    </h2>

                    <!-- Title -->
                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label class="required font-bold">
                            عنوان العرض السريع
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            rules="required"
                            :label="'عنوان العرض'"
                            :placeholder="'مثال: عروض الجمعة الوردية السريعة'"
                        />
                        
                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    <!-- Status -->
                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label class="required font-bold">
                            حالة العرض
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            rules="required"
                            :label="'الحالة'"
                        >
                            <option value="1">مفعّل (Active)</option>
                            <option value="0">غير مفعّل (Inactive)</option>
                        </x-admin::form.control-group.control>
                        
                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>

                    <!-- Starts At -->
                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label class="required font-bold">
                            تاريخ ووقت البدء
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="datetime"
                            name="starts_at"
                            rules="required"
                            :label="'تاريخ ووقت البدء'"
                        />
                        
                        <x-admin::form.control-group.error control-name="starts_at" />
                    </x-admin::form.control-group>

                    <!-- Ends At -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required font-bold">
                            تاريخ ووقت الانتهاء
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="datetime"
                            name="ends_at"
                            rules="required"
                            :label="'تاريخ ووقت الانتهاء'"
                        />
                        
                        <x-admin::form.control-group.error control-name="ends_at" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <!-- Right Side: Interactive Products Manager (8 cols) -->
            <div class="lg:col-span-8">
                <v-flash-deal-items :products-list="{{ json_encode($products) }}"></v-flash-deal-items>
            </div>

        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-flash-deal-items-template">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between border-b dark:border-gray-800 pb-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>المنتجات المشمولة في العرض السريع</span>
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            ابحث عن المنتجات بالاسم أو الـ SKU، ثم حدد سعر الخصم والكمية المتاحة للعرض
                        </p>
                    </div>

                    <button 
                        type="button" 
                        @click="addItem"
                        class="inline-flex items-center gap-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-extrabold text-xs py-2.5 px-4 rounded-xl transition-all border border-amber-500/30 cursor-pointer shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span>إضافة منتج آخر</span>
                    </button>
                </div>

                <!-- Products List Container -->
                <div class="space-y-4">
                    <div 
                        v-for="(item, index) in items" 
                        :key="index"
                        class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 relative hover:border-amber-500/40 transition-all"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            
                            <!-- Product Search Combobox (6 cols) -->
                            <div class="md:col-span-6 relative">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                    اختيار المنتج <span class="text-red-500">*</span>
                                </label>

                                <!-- Hidden Input for Form Submission -->
                                <input type="hidden" :name="'products[' + index + '][product_id]'" :value="item.product_id" required />

                                <!-- Selected State View -->
                                <div v-if="item.product_id && getProduct(item.product_id)" class="flex items-center justify-between bg-white dark:bg-gray-900 border border-amber-500/40 rounded-xl p-2.5 text-xs font-semibold shadow-sm">
                                    <div class="truncate pr-2">
                                        <span class="text-amber-600 dark:text-amber-400 font-bold">#@{{ getProduct(item.product_id).id }}</span>
                                        <span class="text-gray-800 dark:text-gray-200 mx-1.5 font-bold">@{{ getProduct(item.product_id).name }}</span>
                                        <span class="text-gray-400 text-[11px]">(SKU: @{{ getProduct(item.product_id).sku }})</span>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="clearProductSelection(index)"
                                        class="text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400 font-bold underline shrink-0 px-2 cursor-pointer"
                                    >
                                        تغيير
                                    </button>
                                </div>

                                <!-- Search Input Dropdown State -->
                                <div v-else class="relative">
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            v-model="searchQueries[index]" 
                                            @input="onSearchInput(index)"
                                            @focus="openDropdown(index)"
                                            placeholder="🔍 ابحث باسم المنتج، رقم الـ SKU أو ID..." 
                                            class="w-full text-xs font-semibold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                        >
                                        <span v-if="isSearching[index]" class="absolute left-3 top-3 text-xs text-amber-500 animate-spin">⌛</span>
                                    </div>

                                    <!-- Fast Compact Dropdown List -->
                                    <div 
                                        v-if="activeDropdown === index && getDropdownProducts(index).length > 0"
                                        class="absolute z-50 w-full mt-1.5 max-h-56 overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-2xl p-1 divide-y divide-gray-100 dark:divide-gray-800"
                                    >
                                        <div 
                                            v-for="p in getDropdownProducts(index)" 
                                            :key="p.id"
                                            @click="selectProduct(index, p)"
                                            class="p-2.5 hover:bg-amber-500/10 dark:hover:bg-amber-500/20 rounded-lg cursor-pointer transition-colors flex items-center justify-between text-xs"
                                        >
                                            <div class="truncate">
                                                <span class="font-bold text-amber-600 dark:text-amber-400">#@{{ p.id }}</span>
                                                <span class="font-semibold text-gray-800 dark:text-gray-200 mx-1.5">@{{ p.name }}</span>
                                                <span class="text-gray-400 text-[10px]">(@{{ p.sku }})</span>
                                            </div>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-extrabold text-[11px] shrink-0 ml-2">$@{{ p.price }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flash Price Input (3 cols) -->
                            <div class="md:col-span-3">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                        سعر العرض السريع <span class="text-red-500">*</span>
                                    </label>
                                    <span 
                                        v-if="item.product_id && getOriginalPrice(item.product_id)"
                                        class="text-[10px] text-gray-400 line-through font-bold"
                                    >
                                        الأصلي: $@{{ getOriginalPrice(item.product_id) }}
                                    </span>
                                </div>
                                <div class="relative">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        :name="'products[' + index + '][flash_price]'" 
                                        v-model="item.flash_price" 
                                        placeholder="0.00" 
                                        required
                                        class="w-full text-xs font-bold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-amber-600 dark:text-amber-400 rounded-xl p-3 pl-8 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                    >
                                    <span class="absolute left-3 top-3 text-xs font-bold text-gray-400">$</span>
                                </div>
                            </div>

                            <!-- Allocation Quota Input (2 cols) -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                    الكمية المخصصة <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    :name="'products[' + index + '][allocation_qty]'" 
                                    v-model="item.allocation_qty" 
                                    placeholder="مثال: 50" 
                                    required
                                    class="w-full text-xs font-bold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                >
                                <input type="hidden" :name="'products[' + index + '][sold_qty]'" :value="item.sold_qty || 0">
                            </div>

                            <!-- Delete Button (1 col) -->
                            <div class="md:col-span-1 flex justify-center pb-1">
                                <button 
                                    type="button" 
                                    @click="removeItem(index)"
                                    :disabled="items.length === 1"
                                    class="w-9 h-9 rounded-xl bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                                    title="حذف هذا المنتج"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                        </div>

                        <!-- Calculated Discount Preview Ribbon -->
                        <div 
                            v-if="item.product_id && item.flash_price && getDiscountPercent(item.product_id, item.flash_price) > 0"
                            class="mt-3 inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[11px] font-extrabold px-3 py-1 rounded-lg"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>وفر للعميل خصم قدره: <strong>@{{ getDiscountPercent(item.product_id, item.flash_price) }}%</strong> عن السعر الأصلي</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Add Button -->
                <div class="mt-6 text-center">
                    <button 
                        type="button" 
                        @click="addItem"
                        class="inline-flex items-center gap-2 border border-dashed border-amber-500/50 hover:bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-xs py-3 px-8 rounded-xl transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span>إضافة منتج إضافي للعرض السريع</span>
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-flash-deal-items', {
                template: '#v-flash-deal-items-template',

                props: {
                    productsList: {
                        type: Array,
                        default: () => []
                    },
                    initialItems: {
                        type: Array,
                        default: () => []
                    }
                },

                data() {
                    const initialMap = new Map();
                    if (this.productsList) {
                        this.productsList.forEach(p => initialMap.set(p.id, p));
                    }

                    return {
                        allProductsMap: initialMap,
                        items: this.initialItems && this.initialItems.length ? JSON.parse(JSON.stringify(this.initialItems)) : [
                            { product_id: '', flash_price: '', allocation_qty: 50, sold_qty: 0 }
                        ],
                        searchQueries: {},
                        searchResults: {},
                        activeDropdown: null,
                        isSearching: {},
                        debounceTimers: {}
                    };
                },

                methods: {
                    addItem() {
                        this.items.push({ product_id: '', flash_price: '', allocation_qty: 50, sold_qty: 0 });
                    },

                    removeItem(index) {
                        if (this.items.length > 1) {
                            this.items.splice(index, 1);
                        }
                    },

                    getProduct(id) {
                        if (!id) return null;
                        return this.allProductsMap.get(parseInt(id)) || this.productsList.find(p => p.id == id);
                    },

                    getOriginalPrice(id) {
                        const p = this.getProduct(id);
                        return p ? p.price : 0;
                    },

                    getDiscountPercent(id, flashPrice) {
                        const orig = this.getOriginalPrice(id);
                        if (orig > 0 && flashPrice > 0 && orig > flashPrice) {
                            return Math.round(((orig - flashPrice) / orig) * 100);
                        }
                        return 0;
                    },

                    openDropdown(index) {
                        this.activeDropdown = index;
                        if (!this.searchResults[index]) {
                            this.searchResults[index] = this.productsList.slice(0, 30);
                        }
                    },

                    getDropdownProducts(index) {
                        if (this.searchResults[index] && this.searchResults[index].length > 0) {
                            return this.searchResults[index];
                        }
                        return this.productsList.slice(0, 30);
                    },

                    selectProduct(index, product) {
                        this.items[index].product_id = product.id;
                        this.allProductsMap.set(product.id, product);
                        this.activeDropdown = null;
                        this.searchQueries[index] = '';
                    },

                    clearProductSelection(index) {
                        this.items[index].product_id = '';
                        this.searchQueries[index] = '';
                        this.openDropdown(index);
                    },

                    onSearchInput(index) {
                        const query = (this.searchQueries[index] || '').trim();
                        this.activeDropdown = index;

                        if (this.debounceTimers[index]) {
                            clearTimeout(this.debounceTimers[index]);
                        }

                        if (!query) {
                            this.searchResults[index] = this.productsList.slice(0, 30);
                            return;
                        }

                        this.isSearching[index] = true;
                        this.debounceTimers[index] = setTimeout(() => {
                            fetch(`{{ route('admin.marketing.promotions.flash_deals.search_products') }}?query=${encodeURIComponent(query)}`)
                                .then(res => res.json())
                                .then(data => {
                                    this.searchResults[index] = data;
                                    data.forEach(p => this.allProductsMap.set(p.id, p));
                                    this.isSearching[index] = false;
                                })
                                .catch(() => {
                                    this.isSearching[index] = false;
                                });
                        }, 250);
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
