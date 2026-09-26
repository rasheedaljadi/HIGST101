<x-admin::layouts>
    <x-slot:title>
        معاينة سجل المخزون - {{ $source->name }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.sources.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.sources.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $source->name }}</span>
                </div>
                
                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ $source->name }}
                    </h1>
                    <span class="px-2.5 py-0.5 rounded text-xs font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                        {{ $source->code }}
                    </span>
                    @if($source->status)
                        <span class="badge badge-md badge-success">نشط</span>
                    @else
                        <span class="badge badge-md badge-danger">معطل</span>
                    @endif
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    الموقع: <span class="font-medium text-gray-700 dark:text-gray-300">{{ ($source->city ? $source->city . '، ' : '') . $source->country }}</span>
                    @if($source->source_type)
                        | نوع المخزن: <span class="font-medium text-blue-600 dark:text-blue-400">{{ trans("inventory::app.admin.source_types.{$source->source_type}") ?: $source->source_type }}</span>
                    @endif
                    | قابل للبيع: <span class="font-medium {{ $source->is_salable ? 'text-emerald-600' : 'text-gray-500' }}">{{ $source->is_salable ? 'نعم' : 'لا' }}</span>
                    | مصدر تسليم: <span class="font-medium {{ $source->is_delivery_source ? 'text-emerald-600' : 'text-gray-500' }}">{{ $source->is_delivery_source ? 'نعم' : 'لا' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.sources.index') }}" class="secondary-button">
                    ← العودة لقائمة المصادر
                </a>
            </div>
        </div>

@pushOnce('styles')
    <style>
        .warehouse-stats-unified-grid {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1.5rem !important;
            width: 100% !important;
        }
        .warehouse-stat-item {
            flex: 1 1 0 !important;
            min-width: 0 !important;
        }
        .warehouse-stat-divider {
            border-inline-start: 1px solid #e5e7eb !important;
            padding-inline-start: 1.5rem !important;
        }
        .dark .warehouse-stat-divider {
            border-inline-start-color: #1f2937 !important;
        }
        @media (max-width: 767px) {
            .warehouse-stats-unified-grid {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 1rem !important;
            }
            .warehouse-stat-divider {
                border-inline-start: none !important;
                padding-inline-start: 0 !important;
                border-top: 1px solid #e5e7eb !important;
                padding-top: 0.75rem !important;
            }
            .dark .warehouse-stat-divider {
                border-top-color: #1f2937 !important;
            }
        }
    </style>
@endPushOnce

        {{-- Warehouse Summary Stats: Single Unified Section --}}
        <div class="box-shadow rounded-lg bg-white p-5 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="warehouse-stats-unified-grid flex items-center justify-between" style="display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 1.5rem; width: 100%;">
                {{-- Stat 1: Unique Items / SKUs --}}
                <div class="warehouse-stat-item flex items-center gap-4" style="flex: 1 1 0; min-width: 0;">
                    <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <span class="icon-product text-2xl"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">إجمالي الأصناف المسجلة</span>
                        <span class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ number_format($stats->total_items ?? 0) }}
                        </span>
                        <span class="text-[11px] text-gray-400">عنصر / صنف مسجل</span>
                    </div>
                </div>

                {{-- Stat 2: Total Units in Stock --}}
                <div class="warehouse-stat-item warehouse-stat-divider flex items-center gap-4" style="flex: 1 1 0; min-width: 0;">
                    <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <span class="icon-inventory text-2xl"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">إجمالي الكميات المتوفرة</span>
                        <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                            {{ number_format($stats->total_units ?? 0) }}
                        </span>
                        <span class="text-[11px] text-emerald-600/80">قطعة / وحدة فعلية</span>
                    </div>
                </div>

                {{-- Stat 3: In-Stock vs Out-of-Stock --}}
                <div class="warehouse-stat-item warehouse-stat-divider flex items-center gap-4" style="flex: 1 1 0; min-width: 0;">
                    <div class="w-12 h-12 rounded-lg bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <span class="icon-check text-2xl"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">حالة التوفر</span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-sm font-bold text-emerald-600">{{ number_format($stats->in_stock_items ?? 0) }} متوفر</span>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <span class="text-sm font-bold {{ ($stats->out_of_stock_items ?? 0) > 0 ? 'text-rose-600' : 'text-gray-400' }}">{{ number_format($stats->out_of_stock_items ?? 0) }} نفذ</span>
                        </div>
                        <span class="text-[11px] text-gray-400">توزيع جاهزية الأصناف</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notice Banner --}}
        <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-300 shrink-0">
                    <span class="icon-information text-lg"></span>
                </div>
                <p class="text-xs text-slate-700 dark:text-slate-300">
                    يوضح هذا السجل جميع العناصر والمنتجات المرتبطة بمستودع <strong>{{ $source->name }}</strong>. يمكنك البحث وتصفية المنتجات حسب الرمز أو الاسم أو النوع، أو النقر على إجراء المعاينة لعرض بطاقة المنتج وسجل حركاته بالتفصيل.
                </p>
            </div>
        </div>

        {{-- Inventory Items DataGrid Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm">
            <x-admin::datagrid :src="route('admin.inventory.sources.view', $source->id)" />
        </div>
    </div>
</x-admin::layouts>
