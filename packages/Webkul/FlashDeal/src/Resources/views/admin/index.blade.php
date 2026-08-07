<x-admin::layouts>
    <x-slot:title>
        العروض السريعة
    </x-slot>

    {!! view_render_event('bagisto.admin.marketing.promotions.flash_deals.list.before') !!}

    <v-flash-deals-index></v-flash-deals-index>

    {!! view_render_event('bagisto.admin.marketing.promotions.flash_deals.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-flash-deals-index-template">
            <div>
                <!-- Header -->
                <div class="mt-3 flex items-center justify-between gap-4 max-sm:flex-wrap mb-4">
                    <div>
                        <p class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <span class="text-amber-500">⚡</span>
                            <span>العروض السريعة (Flash Deals)</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            أنشئ عروضاً سريعة واضغط على زر "إدارة المنتجات" لإضافة المنتجات وضبط أسعار الخصم والكميات المخصصة
                        </p>
                    </div>

                    <div class="flex items-center gap-x-2.5">
                        @if (bouncer()->hasPermission('marketing.promotions.flash_deals'))
                            <a 
                                href="{{ route('admin.marketing.promotions.flash_deals.create') }}"
                                class="primary-button bg-amber-500 hover:bg-amber-600 text-gray-950 font-black py-2.5 px-5 rounded-xl shadow transition-all flex items-center gap-2 text-xs"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>إنشاء عرض سريع جديد</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- DataGrid -->
                <x-admin::datagrid :src="route('admin.marketing.promotions.flash_deals.index')" ref="datagrid">
                    <template #body="{ isLoading, available, applied, selectAll, sort, performAction }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.body />
                        </template>

                        <template v-else>
                            <div
                                v-for="record in available.records"
                                :key="record.id"
                                class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950"
                                style="grid-template-columns: repeat(6, minmax(150px, 1fr));"
                            >
                                <!-- ID -->
                                <p class="font-mono text-xs text-gray-500">#@{{ record.id }}</p>

                                <!-- Title -->
                                <p class="font-bold text-gray-900 dark:text-white">@{{ record.title }}</p>

                                <!-- Status -->
                                <p>
                                    <span 
                                        class="px-2.5 py-1 rounded-full text-xs font-bold"
                                        :class="record.status == 'نشط' || record.status == 1 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                    >
                                        @{{ record.status }}
                                    </span>
                                </p>

                                <!-- Starts At -->
                                <p class="text-xs font-mono text-gray-600 dark:text-gray-400">@{{ record.starts_at }}</p>

                                <!-- Ends At -->
                                <p class="text-xs font-mono text-gray-600 dark:text-gray-400">@{{ record.ends_at }}</p>

                                <!-- Actions -->
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Manage Products Action Button -->
                                    <button 
                                        type="button"
                                        class="flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/50 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 px-2.5 py-1.5 rounded-xl text-xs font-black transition-all shadow-sm"
                                        title="إدارة المنتجات المشمولة في هذا العرض"
                                        @click="openProductsModal(record.id, record.title)"
                                    >
                                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span>إدارة المنتجات</span>
                                    </button>

                                    <!-- Edit Action Icon -->
                                    <a 
                                        :href="record.actions.find(action => action.icon === 'icon-edit')?.url || `/admin/marketing/promotions/flash-deals/edit/${record.id}`"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300"
                                        title="تعديل العرض"
                                    >
                                        <span class="icon-edit"></span>
                                    </a>

                                    <!-- Delete Action Icon -->
                                    <a 
                                        @click="performAction(record.actions.find(action => action.icon === 'icon-delete'))"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 text-red-600"
                                        title="حذف العرض"
                                    >
                                        <span class="icon-delete"></span>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </template>
                </x-admin::datagrid>

                <!-- Modal for Managing Flash Deal Products -->
                <x-admin::modal ref="productsModal" width="max-w-4xl">
                    <x-slot:header>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⚡</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">
                                إدارة منتجات العرض السريع: @{{ activeDealTitle }}
                            </span>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <div v-if="isLoadingProducts" class="p-12 text-center text-gray-500 dark:text-gray-400 font-bold">
                            جاري تحميل منتجات العرض السريع...
                        </div>

                        <div v-else class="flex flex-col gap-6 p-2">
                            <!-- Search & Add Product Section -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                                <h4 class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase mb-3 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <span>البحث عن منتج رئيسي وإضافته للعرض:</span>
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                                    <!-- Search Input -->
                                    <div class="md:col-span-6 relative">
                                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">
                                            اسم المنتج الرئيسي / SKU / ID:
                                        </label>
                                        <input 
                                            type="text" 
                                            v-model="searchQuery" 
                                            @input="searchProducts" 
                                            placeholder="اكتب للبحث عن منتج..." 
                                            class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white"
                                        />

                                        <!-- Live Search Dropdown -->
                                        <div 
                                            v-if="searchResults.length > 0 && isSearching" 
                                            class="absolute right-0 left-0 top-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-2xl max-h-60 overflow-y-auto z-50 divide-y divide-gray-100 dark:divide-gray-800"
                                        >
                                            <div 
                                                v-for="prod in searchResults" 
                                                :key="prod.id" 
                                                @click="selectProduct(prod)" 
                                                class="p-2.5 hover:bg-amber-50 dark:hover:bg-gray-800 cursor-pointer flex items-center gap-3"
                                            >
                                                <img :src="prod.image" class="w-9 h-9 object-cover rounded-lg border border-gray-200 dark:border-gray-700 bg-white" />
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">@{{ prod.name }}</p>
                                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 mt-0.5">
                                                        <span class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded font-mono">#@{{ prod.id }}</span>
                                                        <span class="font-mono">SKU: @{{ prod.sku }}</span>
                                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">@{{ prod.price }} $</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Flash Price -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">
                                            سعر العرض السريع ($):
                                        </label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            v-model="newFlashPrice" 
                                            placeholder="0.00" 
                                            class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-bold text-red-600 dark:text-red-400"
                                        />
                                    </div>

                                    <!-- Allocation Qty -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">
                                            الكمية المخصصة:
                                        </label>
                                        <div class="flex gap-2">
                                            <input 
                                                type="number" 
                                                v-model="newAllocationQty" 
                                                placeholder="50" 
                                                class="w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white"
                                            />
                                            <button 
                                                type="button" 
                                                @click="addProductToDeal" 
                                                class="bg-amber-500 hover:bg-amber-600 text-gray-950 font-black px-4 py-2 rounded-xl text-xs flex items-center gap-1 shadow transition-all shrink-0"
                                            >
                                                <span>+ إضافة</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Preview Badge -->
                                <div v-if="selectedProduct" class="mt-3 p-2.5 bg-amber-100/60 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <img :src="selectedProduct.image" class="w-7 h-7 object-cover rounded-md bg-white border border-gray-200 dark:border-gray-700" />
                                        <span class="text-xs font-bold text-gray-900 dark:text-white truncate">@{{ selectedProduct.name }}</span>
                                        <span class="text-[10px] bg-white dark:bg-gray-900 px-2 py-0.5 rounded font-mono shrink-0">السعر الأصلي: @{{ selectedProduct.price }} $</span>
                                    </div>
                                    <button type="button" @click="selectedProduct = null" class="text-red-500 text-xs font-bold hover:underline shrink-0 mr-2">إلغاء الاختيار</button>
                                </div>
                            </div>

                            <!-- Products Table List -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">
                                    المنتجات المضافة حالياً في هذا العرض (@{{ dealProducts.length }}):
                                </h4>

                                <div v-if="dealProducts.length === 0" class="p-8 text-center border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-2xl text-gray-400 text-xs">
                                    لا توجد منتجات مضافة لهذا العرض بعد. استخدم حقل البحث أعلاه لإضافة منتج جديد.
                                </div>

                                <div v-else class="border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                                    <table class="w-full text-right text-xs">
                                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold border-b border-gray-200 dark:border-gray-700">
                                            <tr>
                                                <th class="p-3">المنتج الرئيسي</th>
                                                <th class="p-3">السعر الأصلي</th>
                                                <th class="p-3">سعر العرض السريع ($)</th>
                                                <th class="p-3">الكمية المخصصة</th>
                                                <th class="p-3">المباع</th>
                                                <th class="p-3 text-center">إجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                                            <tr v-for="(item, index) in dealProducts" :key="item.product_id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td class="p-3">
                                                    <div class="flex items-center gap-2.5">
                                                        <img :src="item.image" class="w-9 h-9 object-cover rounded-lg border border-gray-200 dark:border-gray-800 bg-white" />
                                                        <div>
                                                            <p class="font-bold text-gray-900 dark:text-white leading-snug">@{{ item.name }}</p>
                                                            <span class="text-[10px] text-gray-400 font-mono">SKU: @{{ item.sku }} | ID: #@{{ item.product_id }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 font-semibold text-gray-500 line-through">
                                                    @{{ item.price }} $
                                                </td>
                                                <td class="p-3">
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        v-model="item.flash_price" 
                                                        class="w-24 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-2 py-1 text-xs font-bold text-red-600 dark:text-red-400"
                                                    />
                                                </td>
                                                <td class="p-3">
                                                    <input 
                                                        type="number" 
                                                        v-model="item.allocation_qty" 
                                                        class="w-20 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-2 py-1 text-xs font-bold"
                                                    />
                                                </td>
                                                <td class="p-3 font-bold text-amber-600">
                                                    @{{ item.sold_qty || 0 }}
                                                </td>
                                                <td class="p-3 text-center">
                                                    <button 
                                                        type="button" 
                                                        @click="removeProductFromDeal(index)" 
                                                        class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-all"
                                                        title="حذف المنتج من العرض"
                                                    >
                                                        <span class="icon-delete text-xl"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-slot>

                    <x-slot:footer>
                        <div class="flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                class="transparent-button border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold py-2 px-5 rounded-xl text-xs"
                                @click="closeModal"
                            >
                                إلغاء
                            </button>

                            <button 
                                type="button" 
                                class="primary-button bg-amber-500 hover:bg-amber-600 text-gray-950 font-black py-2 px-6 rounded-xl text-xs shadow-md transition-all flex items-center gap-1.5"
                                :disabled="isSaving"
                                @click="saveProducts"
                            >
                                <span v-if="isSaving">جاري الحفظ...</span>
                                <span v-else>حفظ منتجات العرض</span>
                            </button>
                        </div>
                    </x-slot>
                </x-admin::modal>
            </div>
        </script>

        <script type="module">
            app.component('v-flash-deals-index', {
                template: '#v-flash-deals-index-template',
                data() {
                    return {
                        activeDealId: null,
                        activeDealTitle: '',
                        isLoadingProducts: false,
                        dealProducts: [],
                        searchQuery: '',
                        searchResults: [],
                        isSearching: false,
                        selectedProduct: null,
                        newFlashPrice: '',
                        newAllocationQty: 50,
                        isSaving: false,
                        searchTimeout: null,
                    };
                },
                methods: {
                    openProductsModal(dealId, title = '') {
                        this.activeDealId = dealId;
                        this.activeDealTitle = title || `العرض #${dealId}`;
                        this.dealProducts = [];
                        this.searchQuery = '';
                        this.searchResults = [];
                        this.selectedProduct = null;
                        this.newFlashPrice = '';
                        this.newAllocationQty = 50;
                        this.isLoadingProducts = true;

                        this.$refs.productsModal.toggle();

                        this.$axios.get(`/admin/marketing/promotions/flash-deals/${dealId}/products`)
                            .then(response => {
                                this.activeDealTitle = response.data.deal.title;
                                this.dealProducts = response.data.products;
                                this.isLoadingProducts = false;
                            })
                            .catch(error => {
                                this.isLoadingProducts = false;
                                this.$emitter.emit('add-flash', { type: 'error', message: 'تعذر تحميل منتجات العرض' });
                            });
                    },
                    closeModal() {
                        this.$refs.productsModal.toggle();
                    },
                    searchProducts() {
                        clearTimeout(this.searchTimeout);
                        if (!this.searchQuery || this.searchQuery.trim().length < 1) {
                            this.searchResults = [];
                            this.isSearching = false;
                            return;
                        }

                        this.searchTimeout = setTimeout(() => {
                            this.$axios.get('/admin/marketing/promotions/flash-deals/search-products', {
                                params: { query: this.searchQuery }
                            })
                            .then(response => {
                                this.searchResults = response.data;
                                this.isSearching = true;
                            })
                            .catch(() => {
                                this.searchResults = [];
                            });
                        }, 300);
                    },
                    selectProduct(prod) {
                        this.selectedProduct = prod;
                        this.newFlashPrice = (prod.price * 0.8).toFixed(2);
                        this.searchQuery = prod.name;
                        this.isSearching = false;
                    },
                    addProductToDeal() {
                        if (!this.selectedProduct) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'يرجى اختيار منتج أولاً من قائمة البحث' });
                            return;
                        }

                        const exists = this.dealProducts.some(p => p.product_id === this.selectedProduct.id);
                        if (exists) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'هذا المنتج مضاف بالفعل في هذا العرض' });
                            return;
                        }

                        const flashPrice = parseFloat(this.newFlashPrice);
                        if (isNaN(flashPrice) || flashPrice < 0) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'يرجى إدخال سعر خصم صالح' });
                            return;
                        }

                        const allocQty = parseInt(this.newAllocationQty);
                        if (isNaN(allocQty) || allocQty < 1) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'يرجى إدخال كمية مخصصة صالحة (1 أو أكثر)' });
                            return;
                        }

                        this.dealProducts.push({
                            id: null,
                            product_id: this.selectedProduct.id,
                            name: this.selectedProduct.name,
                            sku: this.selectedProduct.sku,
                            price: this.selectedProduct.price,
                            image: this.selectedProduct.image,
                            flash_price: flashPrice,
                            allocation_qty: allocQty,
                            sold_qty: 0,
                        });

                        this.selectedProduct = null;
                        this.searchQuery = '';
                        this.newFlashPrice = '';
                        this.newAllocationQty = 50;

                        this.$emitter.emit('add-flash', { type: 'success', message: 'تمت إضافة المنتج للقائمة' });
                    },
                    removeProductFromDeal(index) {
                        this.dealProducts.splice(index, 1);
                    },
                    saveProducts() {
                        this.isSaving = true;

                        const payload = {
                            products: this.dealProducts.map(p => ({
                                product_id: p.product_id,
                                flash_price: p.flash_price,
                                allocation_qty: p.allocation_qty,
                            }))
                        };

                        this.$axios.post(`/admin/marketing/promotions/flash-deals/${this.activeDealId}/products`, payload)
                            .then(response => {
                                this.isSaving = false;
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                this.closeModal();
                                this.$refs.datagrid.get();
                            })
                            .catch(error => {
                                this.isSaving = false;
                                const msg = error.response?.data?.message || 'فشل في حفظ منتجات العرض السريع';
                                this.$emitter.emit('add-flash', { type: 'error', message: msg });
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
