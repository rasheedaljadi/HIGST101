<x-admin::layouts>
    <x-slot:title>
        العروض السريعة
    </x-slot>

    <div class="mt-3 flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            العروض السريعة (Flash Deals)
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('marketing.promotions.flash_deals'))
                <a 
                    href="{{ route('admin.marketing.promotions.flash_deals.create') }}"
                    class="primary-button"
                >
                    إنشاء عرض سريع جديد
                </a>
            @endif
        </div>
    </div>

    {!! view_render_event('bagisto.admin.marketing.promotions.flash_deals.list.before') !!}

    <x-admin::datagrid :src="route('admin.marketing.promotions.flash_deals.index')" />

    {!! view_render_event('bagisto.admin.marketing.promotions.flash_deals.list.after') !!}

</x-admin::layouts>
