<x-admin::layouts>
    <x-slot:title>
        ديون العروض الترويجية (Promo Debts)
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                ديون العروض الترويجية المسجلة
            </p>

            <a href="{{ route('admin.wallet.promotions.monitoring.index') }}" class="secondary-button">
                العودة لمركز الرقابة
            </a>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.promotions.monitoring.debts')" />
    </div>
</x-admin::layouts>
