<x-admin::layouts>
    <x-slot:title>
        الرقابة والتدقيق الداخلي للعروض والمكافآت
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        @include('wallet::admin.layouts.tabs')

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    الرقابة والتدقيق المالي للعروض والمكافآت
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    متابعة الحصص النشطة، الاستخدامات المعتمدة، ديون الاسترجاع، وصندوق الأحداث المالية
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Usages Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.usages') }}" class="group block rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:border-blue-500 hover:shadow-md dark:bg-gray-900 dark:border-gray-800 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <span class="icon-dashboard text-2xl"></span>
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                        سجل التدقيق
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 dark:text-white transition">
                    سجل الاستخدامات
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    عرض جميع العمليات المعتمدة والتسجيلات المحاسبية المرتبطة بالعملاء.
                </p>
                <div class="mt-4 flex items-center text-xs font-semibold text-blue-600 dark:text-blue-400">
                    <span>عرض السجلات</span>
                    <span class="icon-arrow-left text-sm mr-1"></span>
                </div>
            </a>

            <!-- Grants Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.grants') }}" class="group block rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:border-purple-500 hover:shadow-md dark:bg-gray-900 dark:border-gray-800 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <span class="icon-cart text-2xl"></span>
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                        أرصدة الحصص
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 dark:text-white transition">
                    حصص المنح النشطة
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    متابعة الأرصدة الترويجية الممنوحة وتواريخ الصلاحية والاستهلاك.
                </p>
                <div class="mt-4 flex items-center text-xs font-semibold text-purple-600 dark:text-purple-400">
                    <span>عرض الحصص</span>
                    <span class="icon-arrow-left text-sm mr-1"></span>
                </div>
            </a>

            <!-- Debts Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.debts') }}" class="group block rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:border-red-500 hover:shadow-md dark:bg-gray-900 dark:border-gray-800 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                        <span class="icon-down text-2xl"></span>
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        الديون والمطالبات
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-red-600 dark:text-white transition">
                    ديون العروض الترويجية
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    متابعة الديون الناتجة عن عمليات الاسترجاع وتسويتها مع المكافآت الجديدة.
                </p>
                <div class="mt-4 flex items-center text-xs font-semibold text-red-600 dark:text-red-400">
                    <span>عرض الديون</span>
                    <span class="icon-arrow-left text-sm mr-1"></span>
                </div>
            </a>

            <!-- Outbox Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.outbox') }}" class="group block rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:border-amber-500 hover:shadow-md dark:bg-gray-900 dark:border-gray-800 transition">
                <div class="flex items-center justify-between mb-4">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <span class="icon-setting text-2xl"></span>
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        طابور المعالجة
                    </span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-600 dark:text-white transition">
                    صندوق الأحداث المالية
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    مراقبة حالة معالجة الأحداث وحالات الفشل والتعافي الآلي للمهام.
                </p>
                <div class="mt-4 flex items-center text-xs font-semibold text-amber-600 dark:text-amber-400">
                    <span>عرض صندوق الأحداث</span>
                    <span class="icon-arrow-left text-sm mr-1"></span>
                </div>
            </a>
        </div>
    </div>
</x-admin::layouts>
