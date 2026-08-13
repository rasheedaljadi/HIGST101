<x-admin::layouts>
    <x-slot:title>
        صندوق الأحداث المالية للعروض
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                صندوق أحداث العروض الترويجية
            </p>

            <a href="{{ route('admin.wallet.promotions.monitoring.index') }}" class="secondary-button">
                العودة لمركز الرقابة
            </a>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.promotions.monitoring.outbox')" />
    </div>
</x-admin::layouts>
