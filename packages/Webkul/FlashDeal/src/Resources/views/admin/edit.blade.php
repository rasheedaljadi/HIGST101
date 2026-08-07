<x-admin::layouts>
    <x-slot:title>
        تعديل العرض السريع #{{ $deal->id }}
    </x-slot>

    <x-admin::form :action="route('admin.marketing.promotions.flash_deals.update', $deal->id)" method="PUT">
        <div class="flex items-center justify-between gap-4">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                تعديل العرض السريع #{{ $deal->id }} - {{ $deal->title }}
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
                    حفظ التغييرات
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
                            عنوان العرض
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            rules="required"
                            :value="$deal->title"
                            :label="'عنوان العرض'"
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
                                :value="$deal->starts_at?->format('Y-m-d H:i:s')"
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
                                :value="$deal->ends_at?->format('Y-m-d H:i:s')"
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
                            :value="$deal->status"
                            :label="'الحالة'"
                        >
                            <option value="1" {{ $deal->status ? 'selected' : '' }}>نشط (Active)</option>
                            <option value="0" {{ !$deal->status ? 'selected' : '' }}>غير نشط (Inactive)</option>
                        </x-admin::form.control-group.control>
                        
                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>
                </div>

                <!-- Products Selection Section -->
                <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
                    <p class="text-base font-semibold mb-2 text-gray-800 dark:text-white">المنتجات المشمولة في العرض السريع</p>

                    <div class="grid grid-cols-12 gap-3 mb-2 font-bold text-xs text-gray-600 dark:text-gray-400 border-b pb-2">
                        <div class="col-span-4">المنتج (ID / SKU)</div>
                        <div class="col-span-3">سعر العرض السريع</div>
                        <div class="col-span-3">الكمية المخصصة</div>
                        <div class="col-span-2">المبيعات الحالية</div>
                    </div>

                    @foreach ($deal->products as $index => $item)
                        <div class="grid grid-cols-12 gap-3 items-center mb-3">
                            <div class="col-span-4">
                                <select name="products[{{ $index }}][product_id]" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2">
                                    <option value="{{ $item->product_id }}" selected>#{{ $item->product_id }} - {{ $item->product?->sku ?? 'Product' }}</option>
                                </select>
                            </div>
                            <div class="col-span-3">
                                <input type="number" step="0.01" name="products[{{ $index }}][flash_price]" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2" value="{{ $item->flash_price }}">
                            </div>
                            <div class="col-span-3">
                                <input type="number" name="products[{{ $index }}][allocation_qty]" class="w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-lg p-2" value="{{ $item->allocation_qty }}">
                            </div>
                            <div class="col-span-2 text-center text-sm font-bold text-emerald-600">
                                {{ $item->sold_qty }} / {{ $item->allocation_qty }}
                                <input type="hidden" name="products[{{ $index }}][sold_qty]" value="{{ $item->sold_qty }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
