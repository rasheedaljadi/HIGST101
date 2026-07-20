<x-admin::layouts>
    <x-slot:title>
        {{ $pageTitle }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-md:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            {{ $pageTitle }}
        </p>
    </div>

    <div class="mt-8 flex min-h-[60vh] flex-col items-center justify-center rounded-lg border bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900">
        <span class="icon-settings text-6xl text-gray-400 dark:text-gray-500"></span>

        <h2 class="mt-6 text-2xl font-bold text-gray-800 dark:text-white">
            قيد التطوير
        </h2>

        <p class="mt-3 max-w-md text-base text-gray-500 dark:text-gray-400">
            نعمل حالياً على إعداد هذه الميزة. ترقّب إطلاقها قريباً.
        </p>
    </div>
</x-admin::layouts>
