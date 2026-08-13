<x-admin::layouts>
    <x-slot:title>
        شاشات الرقابة والتدقيق الداخلي للعروض
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        @include('wallet::admin.layouts.tabs')

        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                الرقابة والتدقيق المالي للعروض والمكافآت
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                متابعة الحصص النشطة، الاستخدامات، الديون الناتجة عن الاسترجاع، وصندوق الأحداث المالية
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Usages Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.usages') }}" class="rounded-xl border p-6 bg-white shadow-sm hover:border-blue-500 dark:bg-gray-900 dark:border-gray-800 transition">
                <p class="text-sm font-medium text-gray-500">سجل الاستخدامات</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Usage Records</h3>
                <p class="mt-1 text-xs text-blue-600">عرض جميع العمليات المعتمدة والتسجيلات</p>
            </a>

            <!-- Grants Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.grants') }}" class="rounded-xl border p-6 bg-white shadow-sm hover:border-purple-500 dark:bg-gray-900 dark:border-gray-800 transition">
                <p class="text-sm font-medium text-gray-500">حصص المنح النشطة</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Grant Lots</h3>
                <p class="mt-1 text-xs text-purple-600">متابعة الأرصدة الترويجية وتواريخ صلاحيتها</p>
            </a>

            <!-- Debts Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.debts') }}" class="rounded-xl border p-6 bg-white shadow-sm hover:border-red-500 dark:bg-gray-900 dark:border-gray-800 transition">
                <p class="text-sm font-medium text-gray-500">ديون العروض الترويجية</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Promo Debts</h3>
                <p class="mt-1 text-xs text-red-600">الديون الناتجة عن عمليات الاسترجاع</p>
            </a>

            <!-- Outbox Card -->
            <a href="{{ route('admin.wallet.promotions.monitoring.outbox') }}" class="rounded-xl border p-6 bg-white shadow-sm hover:border-amber-500 dark:bg-gray-900 dark:border-gray-800 transition">
                <p class="text-sm font-medium text-gray-500">صندوق الأحداث</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Outbox Queue</h3>
                <p class="mt-1 text-xs text-amber-600">مراقبة معالجة الأحداث وحالات الفشل والمهام</p>
            </a>
        </div>
    </div>
</x-admin::layouts>
