<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.receipts.title') }} - {{ $receipt->receipt_number }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.receipts.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.receipts.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $receipt->receipt_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    {{ $receipt->receipt_number }}
                    <span class="text-xs px-2.5 py-0.5 rounded font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200">
                        {{ $receipt->status }}
                    </span>
                </h1>
            </div>

            <a href="{{ route('admin.inventory.receipts.index') }}" class="secondary-button">
                ← العودة لقائمة الاستلام
            </a>
        </div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Receipt Summary --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b pb-2">
                    بيانات الاستلام والوجهة
                </h2>

                <div class="flex flex-col gap-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">المستودع المستلم:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $receipt->destinationInventorySource?->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">مستودع الحجر:</span>
                        <span>{{ $receipt->quarantineInventorySource?->name ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">مانيفست النقل:</span>
                        <span class="font-bold text-blue-600">{{ $receipt->transferManifest?->manifest_number ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">المرجع الخارجي:</span>
                        <span>{{ $receipt->external_reference ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">فحص وتوثيق بواسطة:</span>
                        <span>{{ $receipt->receivedByAdmin?->name ?: 'Admin' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">تاريخ الاستلام:</span>
                        <span>{{ core()->formatDate($receipt->created_at, 'Y-m-d H:i') }}</span>
                    </div>
                </div>

                {{-- Totals Badges --}}
                <div class="grid grid-cols-3 gap-2 mt-2 pt-2 border-t text-center">
                    <div class="p-2 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                        <span class="block text-lg font-bold">{{ $receipt->total_received_good }}</span>
                        <span class="text-[10px]">سليم</span>
                    </div>
                    <div class="p-2 rounded bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300">
                        <span class="block text-lg font-bold">{{ $receipt->total_received_damaged }}</span>
                        <span class="text-[10px]">تالف</span>
                    </div>
                    <div class="p-2 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300">
                        <span class="block text-lg font-bold">{{ $receipt->total_received_missing }}</span>
                        <span class="text-[10px]">عجز</span>
                    </div>
                </div>
            </div>

            {{-- Items Received List --}}
            <div class="col-span-2 p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b pb-2">
                    تفاصيل البنود المستلمة
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">السليم (Good)</th>
                                <th class="p-2.5">التالف (Damaged)</th>
                                <th class="p-2.5">الناقص (Missing)</th>
                                <th class="p-2.5">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($receipt->items as $item)
                                <tr>
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5 font-bold text-emerald-600">{{ $item->qty_good }}</td>
                                    <td class="p-2.5 font-bold text-rose-600">{{ $item->qty_damaged }}</td>
                                    <td class="p-2.5 font-bold text-amber-600">{{ $item->qty_missing }}</td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800">
                                            {{ $item->condition }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
