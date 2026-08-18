<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.reports.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('inventory::app.admin.menu.inventory') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.reports.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.reports.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('inventory::app.admin.reports.description') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.reports.export', array_merge(['type' => $type], $filters)) }}" class="secondary-button flex items-center gap-2">
                    <span class="icon-export text-xl"></span>
                    {{ trans('inventory::app.admin.reports.export-csv') }}
                </a>
            </div>
        </div>

        {{-- 7 Report Types Tabs Bar --}}
        <div class="flex items-center gap-2 overflow-x-auto border-b border-gray-200 dark:border-gray-800 pb-2">
            @php
                $reportsMap = [
                    'movements' => trans('inventory::app.admin.reports.rep-movements'),
                    'sources' => trans('inventory::app.admin.reports.rep-sources'),
                    'transfers' => trans('inventory::app.admin.reports.rep-transfers'),
                    'receipts' => trans('inventory::app.admin.reports.rep-receipts'),
                    'allocations' => trans('inventory::app.admin.reports.rep-allocations'),
                    'reconciliation' => trans('inventory::app.admin.reports.rep-reconciliation'),
                    'unclassified' => trans('inventory::app.admin.reports.rep-unclassified'),
                ];
            @endphp

            @foreach($reportsMap as $key => $label)
                <a href="{{ route('admin.inventory.reports.index', ['report_type' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors {{ $type === $key ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Filters Section --}}
        <form method="GET" action="{{ route('admin.inventory.reports.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex items-center gap-4 flex-wrap">
            <input type="hidden" name="report_type" value="{{ $type }}">

            <div class="flex items-center gap-2 text-xs">
                <label class="text-gray-500">{{ trans('inventory::app.admin.reports.filter-date-from') }}:</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="px-2.5 py-1.5 rounded border border-gray-300 dark:border-gray-700 bg-transparent text-xs text-gray-800 dark:text-white">
            </div>

            <div class="flex items-center gap-2 text-xs">
                <label class="text-gray-500">{{ trans('inventory::app.admin.reports.filter-date-to') }}:</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="px-2.5 py-1.5 rounded border border-gray-300 dark:border-gray-700 bg-transparent text-xs text-gray-800 dark:text-white">
            </div>

            <div class="flex items-center gap-2 text-xs">
                <label class="text-gray-500">{{ trans('inventory::app.admin.reports.filter-sku') }}:</label>
                <input type="text" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="SKU" class="px-2.5 py-1.5 rounded border border-gray-300 dark:border-gray-700 bg-transparent text-xs text-gray-800 dark:text-white">
            </div>

            <button type="submit" class="primary-button text-xs py-1.5 px-4">
                {{ trans('inventory::app.admin.reports.filter-btn') }}
            </button>
        </form>

        {{-- Report Table Results --}}
        <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white">
                    {{ $reportsMap[$type] ?? 'التقرير' }}
                </h2>
                <span class="text-xs text-gray-500">عدد السجلات: {{ $reportData->count() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-right">
                    <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                        @if($type === 'movements')
                            <tr>
                                <th class="p-2.5">المعرف</th>
                                <th class="p-2.5">النوع</th>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">الكمية</th>
                                <th class="p-2.5">المصدر</th>
                                <th class="p-2.5">الهدف</th>
                                <th class="p-2.5">المرجع</th>
                                <th class="p-2.5">المنفذ</th>
                                <th class="p-2.5">التاريخ</th>
                            </tr>
                        @elseif($type === 'sources')
                            <tr>
                                <th class="p-2.5">الرمز</th>
                                <th class="p-2.5">الاسم</th>
                                <th class="p-2.5">الدولة</th>
                                <th class="p-2.5">النوع</th>
                                <th class="p-2.5">قابل للبيع</th>
                                <th class="p-2.5">عدد المنتجات</th>
                                <th class="p-2.5">إجمالي الرصيد</th>
                            </tr>
                        @elseif($type === 'transfers')
                            <tr>
                                <th class="p-2.5">رقم المانيفست</th>
                                <th class="p-2.5">المصدر</th>
                                <th class="p-2.5">الوجهة</th>
                                <th class="p-2.5">الحالة</th>
                                <th class="p-2.5">الناقل</th>
                                <th class="p-2.5">رقم التتبع</th>
                                <th class="p-2.5">القطع</th>
                                <th class="p-2.5">تاريخ الإرسال</th>
                            </tr>
                        @elseif($type === 'receipts')
                            <tr>
                                <th class="p-2.5">رقم الاستلام</th>
                                <th class="p-2.5">مانيفست النقل</th>
                                <th class="p-2.5">الوجهة</th>
                                <th class="p-2.5">الحالة</th>
                                <th class="p-2.5">السليم</th>
                                <th class="p-2.5">التالف</th>
                                <th class="p-2.5">الناقص</th>
                                <th class="p-2.5">التاريخ</th>
                            </tr>
                        @elseif($type === 'allocations')
                            <tr>
                                <th class="p-2.5">رقم الحجز</th>
                                <th class="p-2.5">الطلب</th>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">نوع التخصيص</th>
                                <th class="p-2.5">المصدر</th>
                                <th class="p-2.5">الكمية المحجوزة</th>
                                <th class="p-2.5">الحالة</th>
                            </tr>
                        @elseif($type === 'reconciliation')
                            <tr>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">المستودع</th>
                                <th class="p-2.5">الرصيد الفعلي (Table)</th>
                                <th class="p-2.5">الرصيد الدفتري (Ledger)</th>
                                <th class="p-2.5">الفارق</th>
                                <th class="p-2.5">حالة المطابقة</th>
                            </tr>
                        @elseif($type === 'unclassified')
                            <tr>
                                <th class="p-2.5">المعرف</th>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">النوع</th>
                                <th class="p-2.5">تصنيف المنشأ</th>
                                <th class="p-2.5">إجمالي المخزون</th>
                                <th class="p-2.5">تاريخ الإنشاء</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($reportData as $row)
                            @php $item = (object) $row; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                @if($type === 'movements')
                                    <td class="p-2.5 text-gray-400">#{{ $item->id }}</td>
                                    <td class="p-2.5 font-medium">{{ $item->movement_type }}</td>
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5 font-bold {{ $item->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $item->quantity }}</td>
                                    <td class="p-2.5 text-gray-500">{{ $item->source_name ?: '-' }}</td>
                                    <td class="p-2.5 text-gray-500">{{ $item->target_name ?: '-' }}</td>
                                    <td class="p-2.5 text-gray-500">{{ $item->order_id ? 'طلب #'.$item->order_id : ($item->reference_event ?: '-') }}</td>
                                    <td class="p-2.5 text-gray-500">{{ $item->actor }}</td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($item->created_at, 'Y-m-d H:i') }}</td>
                                @elseif($type === 'sources')
                                    <td class="p-2.5 font-bold">{{ $item->code }}</td>
                                    <td class="p-2.5">{{ $item->name }}</td>
                                    <td class="p-2.5">{{ $item->country }}</td>
                                    <td class="p-2.5">{{ $item->source_type }}</td>
                                    <td class="p-2.5">{{ $item->is_salable ? 'نعم' : 'لا' }}</td>
                                    <td class="p-2.5">{{ number_format($item->total_skus) }}</td>
                                    <td class="p-2.5 font-bold text-blue-600">{{ number_format($item->total_quantity) }}</td>
                                @elseif($type === 'transfers')
                                    <td class="p-2.5 font-bold text-blue-600">{{ $item->manifest_number }}</td>
                                    <td class="p-2.5">{{ $item->source_name }}</td>
                                    <td class="p-2.5">{{ $item->destination_name }}</td>
                                    <td class="p-2.5"><span class="px-2 py-0.5 rounded text-[10px] bg-gray-100 dark:bg-gray-800">{{ $item->status }}</span></td>
                                    <td class="p-2.5">{{ $item->carrier_name ?: '-' }}</td>
                                    <td class="p-2.5">{{ $item->tracking_number ?: '-' }}</td>
                                    <td class="p-2.5">{{ $item->total_items_count }}</td>
                                    <td class="p-2.5 text-gray-400">{{ $item->dispatched_at ? core()->formatDate($item->dispatched_at, 'Y-m-d H:i') : '-' }}</td>
                                @elseif($type === 'receipts')
                                    <td class="p-2.5 font-bold text-emerald-600">{{ $item->receipt_number }}</td>
                                    <td class="p-2.5">{{ $item->transfer_manifest_number ?: '-' }}</td>
                                    <td class="p-2.5">{{ $item->destination_name }}</td>
                                    <td class="p-2.5">{{ $item->status }}</td>
                                    <td class="p-2.5 text-emerald-600 font-bold">{{ $item->total_received_good }}</td>
                                    <td class="p-2.5 text-rose-600 font-bold">{{ $item->total_received_damaged }}</td>
                                    <td class="p-2.5 text-amber-600 font-bold">{{ $item->total_received_missing }}</td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($item->created_at, 'Y-m-d H:i') }}</td>
                                @elseif($type === 'allocations')
                                    <td class="p-2.5 font-bold">#{{ $item->id }}</td>
                                    <td class="p-2.5 font-bold text-blue-600">#{{ $item->order_increment_id ?: $item->order_id }}</td>
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5">{{ $item->allocation_type }}</td>
                                    <td class="p-2.5">{{ $item->source_code }}</td>
                                    <td class="p-2.5 font-bold text-amber-600">{{ $item->reserved_qty }}</td>
                                    <td class="p-2.5">{{ $item->state }}</td>
                                @elseif($type === 'reconciliation')
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5">{{ $item->source_name }}</td>
                                    <td class="p-2.5 font-bold">{{ number_format($item->actual_stock) }}</td>
                                    <td class="p-2.5 font-bold">{{ number_format($item->ledger_stock) }}</td>
                                    <td class="p-2.5 font-bold {{ $item->difference == 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $item->difference }}</td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $item->status === 'Matched' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $item->status === 'Matched' ? 'مطابق 100%' : 'يوجد فارق' }}
                                        </span>
                                    </td>
                                @elseif($type === 'unclassified')
                                    <td class="p-2.5 text-gray-400">#{{ $item->id }}</td>
                                    <td class="p-2.5 font-bold">{{ $item->sku }}</td>
                                    <td class="p-2.5">{{ $item->type }}</td>
                                    <td class="p-2.5 text-rose-600 font-bold">{{ $item->origin_type }}</td>
                                    <td class="p-2.5 font-bold">{{ number_format($item->total_stock) }}</td>
                                    <td class="p-2.5 text-gray-400">{{ core()->formatDate($item->created_at, 'Y-m-d H:i') }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-gray-400">لا توجد بيانات مطابقة للتقرير المحدد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin::layouts>
