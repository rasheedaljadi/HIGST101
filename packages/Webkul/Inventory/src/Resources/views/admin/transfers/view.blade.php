<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.transfers.title') }} - {{ $manifest->manifest_number }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.transfers.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.transfers.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $manifest->manifest_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    {{ $manifest->manifest_number }}
                    <span class="text-xs px-2.5 py-0.5 rounded font-semibold bg-gray-100 dark:bg-gray-800">
                        {{ trans("inventory::app.admin.transfer_statuses.{$manifest->status->value ?? $manifest->status}") ?: $manifest->status }}
                    </span>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.transfers.index') }}" class="secondary-button">
                    ← العودة للقائمة
                </a>

                @if(($manifest->status->value ?? $manifest->status) === 'draft')
                    <form method="POST" action="{{ route('admin.inventory.transfers.dispatch', $manifest->id) }}">
                        @csrf
                        <button type="submit" class="primary-button" onclick="return confirm('{{ trans('inventory::app.admin.transfers.dispatch-confirm') }}')">
                            {{ trans('inventory::app.admin.transfers.dispatch-action') }}
                        </button>
                    </form>
                @endif

                @if(($manifest->status->value ?? $manifest->status) === 'in_transit')
                    <a href="{{ route('admin.inventory.receipts.create', ['transfer_manifest_id' => $manifest->id]) }}" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                        {{ trans('inventory::app.admin.transfers.open-receipt') }}
                    </a>
                @endif
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Manifest Summary Card --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b pb-2">
                    بيانات الشحن والمسار
                </h2>

                <div class="flex flex-col gap-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-500">المصدر:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $manifest->sourceInventorySource?->name }} ({{ $manifest->sourceInventorySource?->country }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">الوجهة:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $manifest->destinationInventorySource?->name }} ({{ $manifest->destinationInventorySource?->country }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">الناقل:</span>
                        <span>{{ $manifest->carrier_name ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">رقم التتبع:</span>
                        <span class="font-bold text-blue-600">{{ $manifest->tracking_number ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">عدد الطرود:</span>
                        <span>{{ $manifest->total_packages }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">تاريخ الإرسال:</span>
                        <span>{{ $manifest->dispatched_at ? core()->formatDate($manifest->dispatched_at, 'Y-m-d H:i') : 'لم يرسل بعد' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">تاريخ الاستلام:</span>
                        <span>{{ $manifest->received_at ? core()->formatDate($manifest->received_at, 'Y-m-d H:i') : 'لم يستلم بعد' }}</span>
                    </div>
                </div>
            </div>

            {{-- Items List --}}
            <div class="col-span-2 p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                <h2 class="text-sm font-bold text-gray-800 dark:text-white border-b pb-2 flex items-center justify-between">
                    <span>البنود المشحونة ({{ $manifest->items->count() }})</span>
                    <span class="text-xs text-gray-500">إجمالي القطع المشحونة: {{ $manifest->items->sum('qty_shipped') }}</span>
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">الكمية المشحونة</th>
                                <th class="p-2.5">المستلم سليم</th>
                                <th class="p-2.5">المستلم تالف</th>
                                <th class="p-2.5">الناقص</th>
                                <th class="p-2.5">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($manifest->items as $item)
                                <tr>
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5 font-bold text-blue-600">{{ $item->qty_shipped }}</td>
                                    <td class="p-2.5 text-emerald-600 font-semibold">{{ $item->qty_received_good }}</td>
                                    <td class="p-2.5 text-rose-600 font-semibold">{{ $item->qty_received_damaged }}</td>
                                    <td class="p-2.5 text-amber-600 font-semibold">{{ $item->qty_received_missing }}</td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800">
                                            {{ $item->item_condition }}
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
