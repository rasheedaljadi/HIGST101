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
                    حدد اسم العرض ومواعيد البدء والانتهاء والحالة. (يمكنك إضافة المنتجات للعرض بعد إنشائه عبر زر إدارة المنتجات)
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.marketing.promotions.flash_deals.index') }}"
                    class="transparent-button border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold py-2.5 px-5 rounded-xl transition-all text-xs"
                >
                    إلغاء
                </a>

                <button
                    type="submit"
                    class="primary-button bg-amber-500 hover:bg-amber-600 text-gray-950 font-black py-2.5 px-6 rounded-xl shadow-md transition-all flex items-center gap-2 text-xs"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>حفظ العرض السريع</span>
                </button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
                <h2 class="text-base font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2 border-b dark:border-gray-800 pb-3">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>بيانات العرض السريع الأساسية</span>
                </h2>

                <!-- Title -->
                <x-admin::form.control-group class="mb-5">
                    <x-admin::form.control-group.label class="required font-bold">
                        عنوان العرض السريع (اسم العرض)
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="title"
                        rules="required"
                        :label="'عنوان العرض'"
                        :placeholder="'مثال: عروض الجمعة السريعة'"
                    />
                    
                    <x-admin::form.control-group.error control-name="title" />
                </x-admin::form.control-group>

                <!-- Status -->
                <x-admin::form.control-group class="mb-5">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    <x-admin::form.control-group class="mb-4">
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
        </div>
    </x-admin::form>
</x-admin::layouts>
