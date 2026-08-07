<x-admin::layouts>
    <x-slot:title>
        إنشاء عرض سريع جديد
    </x-slot>

    <x-admin::form :action="route('admin.marketing.promotions.flash_deals.store')">
        <div class="flex items-center justify-between gap-4">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                إنشاء عرض سريع جديد
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.marketing.promotions.flash_deals.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 font-bold py-2 px-4 rounded-lg"
                >
                    إلغاء
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    حفظ العرض السريع
                </button>
            </div>
        </div>

        <div class="mt-7 flex gap-4 max-xl:flex-col">
            <!-- Left Panel: Deal Parameters -->
            <div class="flex flex-col gap-4 flex-1">
                <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <p class="text-base font-semibold mb-4 text-gray-800 dark:text-white">تفاصيل العرض السريع</p>

                    <!-- Title -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            عنوان العرض (مثال: عروض الجمعة البيضاء السريعة)
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            rules="required"
                            :label="'عنوان العرض'"
                            :placeholder="'أدخل عنوان العرض'"
                        />
                        
                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    <!-- Dates Grid -->
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
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

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
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

                    <!-- Status -->
                    <x-admin::form.control-group class="mt-4">
                        <x-admin::form.control-group.label class="required">
                            الحالة
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="status"
                            rules="required"
                            :label="'الحالة'"
                        >
                            <option value="1">نشط (Active)</option>
                            <option value="0">غير نشط (Inactive)</option>
                        </x-admin::form.control-group.control>
                        
                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>
                </div>

                <!-- Products Selection Section -->
                <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <p class="text-base font-semibold mb-2 text-gray-800 dark:text-white">المنتجات المشمولة في العرض السريع</p>
                    <p class="text-xs text-gray-500 mb-4">حدد المنتجات وسعر العرض والحصة المخصصة للبيع (FOMO Allocation)</p>

                    <div class="grid grid-cols-12 gap-3 mb-2 font-bold text-xs text-gray-600 dark:text-gray-400 border-b pb-2">
                        <div class="col-span-5">المنتج (رقم المنتج ID / SKU)</div>
                        <div class="col-span-3">سعر العرض السريع (USD/SAR)</div>
                        <div class="col-span-4">الكمية المخصصة للعرض (Quota)</div>
                    </div>

                    @for ($i = 0; $i < 3; $i++)
                        <div class="grid grid-cols-12 gap-3 items-center mb-3">
                            <div class="col-span-5">
                                <select name="products[{{ $i }}][product_id]" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2">
                                    <option value="">-- اختر منتجاً --</option>
                                    @foreach ($products as $prod)
                                        <option value="{{ $prod->id }}" {{ $i == 0 && $loop->first ? 'selected' : '' }}>
                                            #{{ $prod->id }} - {{ $prod->sku }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-3">
                                <input type="number" step="0.01" name="products[{{ $i }}][flash_price]" placeholder="السعر" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2" value="{{ $i == 0 ? '19.99' : '' }}">
                            </div>
                            <div class="col-span-4">
                                <input type="number" name="products[{{ $i }}][allocation_qty]" placeholder="الكمية" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2" value="{{ $i == 0 ? '50' : '' }}">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
