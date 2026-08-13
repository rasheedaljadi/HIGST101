<x-admin::layouts>
    <x-slot:title>
        سجل استخدامات العروض الترويجية
    </x-slot:title>

    <div class="flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                سجل استخدامات العروض الترويجية
            </p>

            <a href="{{ route('admin.wallet.promotions.monitoring.index') }}" class="secondary-button">
                العودة لمركز الرقابة
            </a>
        </div>

        <x-admin::datagrid :src="route('admin.wallet.promotions.monitoring.usages')" />
    </div>
</x-admin::layouts>
