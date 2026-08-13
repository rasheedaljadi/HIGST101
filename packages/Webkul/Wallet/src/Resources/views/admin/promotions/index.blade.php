<x-admin::layouts>
    <x-slot:title>
        العروض والمكافآت الترويجية
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    إدارة عروض ومكافآت المحفظة
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    إنشاء وإدارة حملات الترحيب، البونص، والاسترداد النقدي (Cashback)
                </p>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.wallet.promotions.create') }}"
                    class="primary-button"
                >
                    + إنشاء عرض جديد
                </a>
            </div>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.promotions.index')" />
    </div>
</x-admin::layouts>
