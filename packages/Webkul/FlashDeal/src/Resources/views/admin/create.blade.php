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
                <div 
                    v-pre
                    x-data="{
                        productsList: {{ json_encode($products) }},
                        items: [
                            { product_id: '', flash_price: '', allocation_qty: 50 }
                        ],
                        addItem() {
                            this.items.push({ product_id: '', flash_price: '', allocation_qty: 50 });
                        },
                        removeItem(index) {
                            if (this.items.length > 1) {
                                this.items.splice(index, 1);
                            }
                        },
                        getProduct(id) {
                            return this.productsList.find(p => p.id == id);
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
                        }
                    }"
                    class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800"
                >
                    <div class="flex items-center justify-between border-b dark:border-gray-800 pb-4 mb-6">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span>المنتجات المشمولة في العرض السريع</span>
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                حدد المنتجات وسعر الخصم والكمية المتاحة للعرض (FOMO Allocation)
                            </p>
                        </div>

                        <button 
                            type="button" 
                            @click="addItem()"
                            class="inline-flex items-center gap-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-extrabold text-xs py-2 px-4 rounded-xl transition-all border border-amber-500/30"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>إضافة منتج آخر</span>
                        </button>
                    </div>

                    <!-- Products List Container -->
                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 relative hover:border-amber-500/50 transition-colors">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    
                                    <!-- Product Select (5 cols) -->
                                    <div class="md:col-span-5">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                            اختيار المنتج <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            :name="`products[${index}][product_id]`" 
                                            x-model="item.product_id" 
                                            required
                                            class="w-full text-xs font-semibold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                        >
                                            <option value="">-- اختر منتجاً من القائمة --</option>
                                            <template x-for="p in productsList" :key="p.id">
                                                <option :value="p.id" x-text="`#${p.id} - ${p.name} (${p.sku})`"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Flash Price Input (3 cols) -->
                                    <div class="md:col-span-3">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                                سعر العرض السريع <span class="text-red-500">*</span>
                                            </label>
                                            <span 
                                                x-show="item.product_id && getOriginalPrice(item.product_id)"
                                                class="text-[10px] text-gray-400 line-through"
                                                x-text="`الأصلي: $${getOriginalPrice(item.product_id)}`"
                                            ></span>
                                        </div>
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                step="0.01" 
                                                :name="`products[${index}][flash_price]`" 
                                                x-model="item.flash_price" 
                                                placeholder="0.00" 
                                                required
                                                class="w-full text-xs font-bold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-amber-600 dark:text-amber-400 rounded-xl p-3 pl-8 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                            >
                                            <span class="absolute left-3 top-3 text-xs font-bold text-gray-400">$</span>
                                        </div>
                                    </div>

                                    <!-- Allocation Quota Input (3 cols) -->
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                            الكمية المخصصة (Quota) <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="number" 
                                            :name="`products[${index}][allocation_qty]`" 
                                            x-model="item.allocation_qty" 
                                            placeholder="مثال: 50" 
                                            required
                                            class="w-full text-xs font-bold border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-xl p-3 focus:ring-2 focus:ring-amber-500 shadow-sm"
                                        >
                                    </div>

                                    <!-- Delete Button (1 col) -->
                                    <div class="md:col-span-1 flex justify-center pb-1">
                                        <button 
                                            type="button" 
                                            @click="removeItem(index)"
                                            :disabled="items.length === 1"
                                            class="w-9 h-9 rounded-xl bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
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
                                    x-show="item.product_id && item.flash_price && getDiscountPercent(item.product_id, item.flash_price) > 0"
                                    class="mt-3 inline-flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[11px] font-extrabold px-3 py-1 rounded-lg"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>وفر للعميل خصم قدره: <strong x-text="getDiscountPercent(item.product_id, item.flash_price) + '%'"></strong> عن السعر الأصلي</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Add Button -->
                    <div class="mt-6 text-center">
                        <button 
                            type="button" 
                            @click="addItem()"
                            class="inline-flex items-center gap-2 border border-dashed border-amber-500/50 hover:bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-xs py-3 px-8 rounded-xl transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>إضافة منتج إضافي للعرض السريع</span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </x-admin::form>
</x-admin::layouts>
