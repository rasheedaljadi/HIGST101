<x-admin::layouts>
    <x-slot:title>
        إدارة مزامنة AliExpress
    </x-slot>

    <div class="flex flex-col gap-6 pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white font-sans">
                    إدارة مزامنة AliExpress
                </h1>
                <p class="text-sm text-gray-550 dark:text-gray-400 mt-1 font-sans">
                    مراقبة وتحديث الأسعار والمخزون، وتتبع جلسات التزامن وأحداث الصادر والوارد.
                </p>
            </div>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-850 gap-6">
            <button
                type="button"
                class="ae-tab-btn py-3 text-sm font-bold border-b-2 focus:outline-none transition-all font-sans"
                data-tab="imported-products"
            >
                المنتجات المستوردة
            </button>
            <button
                type="button"
                class="ae-tab-btn py-3 text-sm font-bold border-b-2 focus:outline-none transition-all font-sans"
                data-tab="sync-runs"
            >
                سجل جلسات المزامنة المجدولة
            </button>
            <button
                type="button"
                class="ae-tab-btn py-3 text-sm font-bold border-b-2 focus:outline-none transition-all font-sans"
                data-tab="events-tracker"
            >
                تعقب الأحداث (Outbox / Inbox)
            </button>
        </div>

        {{-- Tab Content: Imported Products --}}
        <div id="tab-content-imported-products" class="ae-tab-content flex flex-col gap-6">
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-amber-500">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">منتجات آخر مزامنة</span>
                        <span id="stat-total-count" class="text-3xl font-bold text-gray-800 dark:text-white mt-2 font-sans">{{ $totalCount }}</span>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-500">
                        <span class="icon-product text-2xl"></span>
                    </div>
                </div>

                <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-emerald-500">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">مزامنات ناجحة</span>
                        <span id="stat-success-count" class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2 font-sans">{{ $successCount }}</span>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <span class="icon-toast-done text-2xl"></span>
                    </div>
                </div>

                <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-rose-500">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap font-sans">مزامنات فشلت</span>
                        <span id="stat-failed-count" class="text-3xl font-bold text-rose-600 dark:text-rose-400 mt-2 font-sans">{{ $failedCount }}</span>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/20 rounded-full flex items-center justify-center text-rose-600 dark:text-rose-450">
                        <span class="icon-cancel text-2xl"></span>
                    </div>
                </div>
            </div>

            {{-- Filters & Search --}}
            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form method="GET" action="{{ route('admin.dropshipping.sync.index') }}" class="flex items-center gap-2 w-full md:max-w-md">
                    <input type="hidden" name="active_tab" value="imported-products" />
                    <div class="relative w-full">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="ابحث بمعرف AliExpress، أو رمز SKU، أو المعرف المحلي..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                        />
                        <span class="absolute left-3 top-2.5 text-gray-400 icon-search text-lg"></span>
                    </div>
                    <button type="submit" class="secondary-button whitespace-nowrap py-2 px-4 focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans font-semibold text-sm">
                        بحث
                    </button>
                    @if(!empty($search))
                        <a href="{{ route('admin.dropshipping.sync.index', ['active_tab' => 'imported-products']) }}" class="text-sm text-gray-500 hover:text-red-500 underline ml-2 whitespace-nowrap font-sans">
                            إلغاء
                        </a>
                    @endif
                </form>

                <div>
                    <button
                        type="button"
                        id="ae-sync-all-btn"
                        class="primary-button py-2 px-4 focus:ring-1 focus:ring-amber-500 focus:outline-none transition-all flex items-center gap-2 font-sans font-semibold text-sm"
                    >
                        <span class="icon-settings text-lg"></span>
                        مزامنة كل المنتجات
                    </button>
                </div>
            </div>

            {{-- Bulk Sync Progress Panel --}}
            <div id="ae-bulk-progress-panel" class="hidden p-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 rounded-lg flex-col gap-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-md font-bold text-amber-800 dark:text-amber-300 font-sans" id="bulk-progress-title">
                        جاري تشغيل المزامنة الجماعية للمنتجات...
                    </span>
                    <span class="text-sm font-bold text-amber-700 dark:text-amber-400 font-sans" id="bulk-progress-percentage">0%</span>
                </div>

                <div class="h-3.5 w-full bg-gray-200 dark:bg-gray-850 rounded-full overflow-hidden border border-gray-300/30">
                    <div
                        id="bulk-progress-bar"
                        class="h-3.5 rounded-full transition-all duration-300 ease-out bg-gradient-to-r from-amber-500 to-yellow-600"
                        style="width: 0%;"
                    ></div>
                </div>

                <div class="flex flex-col gap-2">
                    <p id="bulk-progress-log-title" class="text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">السجل الفوري:</p>
                    <div id="bulk-progress-log" class="max-h-32 overflow-y-auto bg-white dark:bg-gray-900 border dark:border-gray-800 p-3 rounded text-xs font-mono flex flex-col gap-1 text-gray-600 dark:text-gray-300">
                        {{-- Appended items go here --}}
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="button" id="ae-cancel-bulk-btn" class="secondary-button text-xs py-1 px-3 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                        إيقاف المزامنة
                    </button>
                </div>
            </div>

            {{-- Main Table Grid --}}
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                <th class="p-4 text-center w-16">المعرف</th>
                                <th class="p-4 w-64">المنتج المحلي</th>
                                <th class="p-4">معرف AliExpress</th>
                                <th class="p-4 text-center">الحالة</th>
                                <th class="p-4">آخر تحديث</th>
                                <th class="p-4 text-center">العمليات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                            @forelse($imports as $import)
                                <tr id="import-row-{{ $import->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200">
                                    <td class="p-4 text-center font-semibold text-gray-500 font-mono">{{ $import->id }}</td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            @if($import->product && $import->product->base_image_url)
                                                <img src="{{ $import->product->base_image_url }}" alt="Product Image" class="w-12 h-12 rounded object-cover border border-gray-200 dark:border-gray-700" />
                                            @else
                                                <div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-850 border dark:border-gray-800 flex items-center justify-center text-gray-400">
                                                    <span class="icon-product text-xl"></span>
                                                </div>
                                            @endif
                                            <div class="flex flex-col">
                                                @if($import->product_id && $import->product)
                                                    <a href="{{ route('admin.catalog.products.edit', $import->product_id) }}" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline text-xs line-clamp-2 font-sans">
                                                        {{ $import->product->name }}
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400 italic font-sans">غير مرتبط بمنتج محلي</span>
                                                @endif
                                                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-sans">رمز SKU: {{ $import->sku ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 font-mono text-xs">
                                        <a href="https://www.aliexpress.com/item/{{ $import->aliexpress_product_id }}.html" target="_blank" class="text-amber-700 dark:text-amber-500 hover:underline flex items-center gap-1">
                                            {{ $import->aliexpress_product_id }}
                                            <span class="icon-arrow-right text-[10px] transform rotate-[-45deg]"></span>
                                        </a>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($import->status === 'success')
                                            <span class="status-badge inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 font-sans">
                                                ● مكتمل
                                            </span>
                                        @elseif($import->status === 'failed')
                                            <span class="status-badge inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-950/20 px-2.5 py-1 text-xs font-semibold text-red-800 dark:text-red-400 border border-red-200 dark:border-red-900/50 font-sans">
                                                ● فشل
                                            </span>
                                        @else
                                            <span class="status-badge inline-flex items-center gap-1 rounded-full bg-yellow-50 dark:bg-yellow-950/20 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:text-yellow-450 border border-yellow-250 dark:border-yellow-900/50 font-sans">
                                                ● {{ $import->status }}
                                            </span>
                                        @endif

                                        @if(!empty($import->error))
                                            <button
                                                type="button"
                                                class="text-xs text-red-500 hover:text-red-700 underline block mt-1 w-full text-center hover:cursor-pointer"
                                                onclick="toggleError('err-{{ $import->id }}')"
                                            >
                                                تفاصيل الخطأ
                                            </button>
                                        @endif
                                    </td>
                                    <td class="p-4 text-xs text-gray-650 dark:text-gray-400 font-mono last-updated">
                                        {{ $import->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <button
                                            type="button"
                                            class="secondary-button py-1 px-3 text-xs bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded flex items-center justify-center gap-1 m-auto ae-row-sync-btn"
                                            data-id="{{ $import->id }}"
                                            @if(!$import->product_id) disabled @endif
                                        >
                                            <span class="icon-settings spinner-icon animate-spin-slow"></span>
                                            مزامنة الآن
                                        </button>
                                    </td>
                                </tr>
                                {{-- Error log row --}}
                                @if(!empty($import->error))
                                    <tr id="err-{{ $import->id }}" class="hidden bg-red-50/30 dark:bg-red-950/10">
                                        <td colspan="6" class="p-4 text-xs text-red-600 dark:text-red-400 border-l border-red-500">
                                            <div class="flex flex-col gap-1 pr-6 font-mono whitespace-pre-line text-right">
                                                <strong>رسالة الخطأ المسجلة:</strong>
                                                {{ $import->error }}
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="icon-product text-5xl text-gray-300 mb-2"></span>
                                            <p class="text-lg font-semibold">لا توجد منتجات مستوردة حالياً</p>
                                            <p class="text-sm mt-1">ابدأ باستيراد المنتجات من لوحة AliExpress أولاً.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($imports->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                        {!! $imports->links() !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Tab Content: Sync Runs History --}}
        <div id="tab-content-sync-runs" class="ae-tab-content hidden flex flex-col gap-6">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">سجل جلسات المزامنة المجدولة (Sync Runs)</h2>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                <th class="p-4 w-32">معرّف الجلسة</th>
                                <th class="p-4">الموفر</th>
                                <th class="p-4 text-center">الحالة</th>
                                <th class="p-4">إحصائيات التشغيل</th>
                                <th class="p-4">بدء التشغيل</th>
                                <th class="p-4">وقت الانتهاء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                            @forelse($syncRuns as $run)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200">
                                    <td class="p-4 font-mono text-xs" title="{{ $run->id }}">{{ substr($run->id, 0, 8) }}...</td>
                                    <td class="p-4 font-semibold text-gray-700 dark:text-gray-300 font-sans">{{ $run->provider }}</td>
                                    <td class="p-4 text-center">
                                        @if($run->status === 'COMPLETED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 font-sans">● مكتمل</span>
                                        @elseif($run->status === 'RUNNING')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-950/20 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50 animate-pulse font-sans">● قيد التشغيل</span>
                                        @elseif($run->status === 'FAILED')
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-950/20 px-2.5 py-1 text-xs font-semibold text-red-800 dark:text-red-400 border border-red-200 dark:border-red-900/50 font-sans">● فشل</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 dark:bg-yellow-950/20 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:text-yellow-450 border border-yellow-250 dark:border-yellow-900/50 font-sans">● {{ $run->status }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-xs font-mono text-gray-500 dark:text-gray-400">
                                        @if($run->statistics)
                                            <div class="flex flex-col gap-0.5">
                                                <span>إجمالي العناصر: {{ $run->statistics['total_items'] ?? 0 }}</span>
                                                <span>تمت المزامنة: {{ $run->statistics['synced_items'] ?? 0 }}</span>
                                                <span>فشل: {{ $run->statistics['failed_items'] ?? 0 }}</span>
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-4 text-xs text-gray-650 dark:text-gray-400 font-mono">
                                        {{ $run->started_at ? $run->started_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="p-4 text-xs text-gray-650 dark:text-gray-400 font-mono">
                                        {{ $run->completed_at ? $run->completed_at->diffForHumans() : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                        لا توجد جلسات مزامنة مجدولة مسجلة حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($syncRuns->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                        {!! $syncRuns->appends(['active_tab' => 'sync-runs'])->links() !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Tab Content: Events Tracker (Outbox & Inbox) --}}
        <div id="tab-content-events-tracker" class="ae-tab-content hidden flex flex-col gap-8">
            {{-- Section A: Outbox Events --}}
            <div class="flex flex-col gap-4">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">أحداث الصادر للـ Domain (Domain Outbox Events)</h2>
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-right text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                    <th class="p-4 w-16">المعرف</th>
                                    <th class="p-4">اسم الحدث</th>
                                    <th class="p-4">الـ Aggregate</th>
                                    <th class="p-4 text-center">الحالة</th>
                                    <th class="p-4 text-center">المحاولات</th>
                                    <th class="p-4">تاريخ الحدث</th>
                                    <th class="p-4 text-center">العمليات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                                @forelse($outboxEvents as $event)
                                    <tr id="outbox-row-{{ $event->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200">
                                        <td class="p-4 font-mono text-xs">{{ $event->id }}</td>
                                        <td class="p-4 font-semibold text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $event->event_name }}</td>
                                        <td class="p-4 text-xs font-sans text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col">
                                                <span>{{ $event->aggregate_type ?? '-' }}</span>
                                                <span class="font-mono mt-0.5">ID: {{ $event->aggregate_id ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            @if($event->status === 'processed')
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-250 font-sans">● مكتمل</span>
                                            @elseif($event->status === 'failed')
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-950/20 px-2.5 py-1 text-xs font-semibold text-red-800 dark:text-red-400 border border-red-250 font-sans">● فشل</span>
                                            @else
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-yellow-50 dark:bg-yellow-950/20 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:text-yellow-450 border border-yellow-250 font-sans">● {{ $event->status }}</span>
                                            @endif

                                            @if(!empty($outboxErrors[$event->id]))
                                                <button
                                                    type="button"
                                                    class="text-xs text-red-500 hover:text-red-700 underline block mt-1 w-full text-center hover:cursor-pointer"
                                                    onclick="toggleError('outbox-err-{{ $event->id }}')"
                                                >
                                                    تفاصيل الخطأ
                                                </button>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center font-mono text-xs">{{ $event->attempts }}</td>
                                        <td class="p-4 text-xs text-gray-650 dark:text-gray-400 font-mono">
                                            {{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}
                                        </td>
                                        <td class="p-4 text-center">
                                            <button
                                                type="button"
                                                class="secondary-button py-1 px-3 text-xs bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded flex items-center justify-center gap-1 m-auto ae-outbox-replay-btn"
                                                data-id="{{ $event->id }}"
                                            >
                                                <span class="icon-settings spinner-icon animate-spin-slow"></span>
                                                إعادة تشغيل
                                            </button>
                                        </td>
                                    </tr>
                                    {{-- Error detail row --}}
                                    @if(!empty($outboxErrors[$event->id]))
                                        <tr id="outbox-err-{{ $event->id }}" class="hidden bg-red-50/30 dark:bg-red-950/10">
                                            <td colspan="7" class="p-4 text-xs text-red-600 dark:text-red-400 border-l border-red-500">
                                                <div class="flex flex-col gap-1 pr-6 font-mono whitespace-pre-line text-right">
                                                    <strong>تفاصيل خطأ الصادر:</strong>
                                                    {{ $outboxErrors[$event->id] }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                            لا توجد أحداث صادر مسجلة حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($outboxEvents->hasPages())
                        <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                            {!! $outboxEvents->appends(['active_tab' => 'events-tracker'])->links() !!}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Section B: Inbox Events --}}
            <div class="flex flex-col gap-4">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">أحداث الوارد الخارجية (External Inbox Events)</h2>
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-right text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                    <th class="p-4 w-16">المعرف</th>
                                    <th class="p-4">الموفر</th>
                                    <th class="p-4">نوع الحدث</th>
                                    <th class="p-4 text-center">الحالة</th>
                                    <th class="p-4 text-center">المحاولات</th>
                                    <th class="p-4">تاريخ الاستلام</th>
                                    <th class="p-4 text-center">العمليات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                                @forelse($inboxEvents as $event)
                                    <tr id="inbox-row-{{ $event->id }}" class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200">
                                        <td class="p-4 font-mono text-xs">{{ $event->id }}</td>
                                        <td class="p-4 font-semibold text-gray-700 dark:text-gray-300 font-sans text-xs">{{ $event->provider }}</td>
                                        <td class="p-4 font-mono text-xs text-gray-500">{{ $event->event_type }}</td>
                                        <td class="p-4 text-center">
                                            @if($event->status === 'processed')
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-250 font-sans">● مكتمل</span>
                                            @elseif($event->status === 'dead_letter' || $event->status === 'failed')
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-950/20 px-2.5 py-1 text-xs font-semibold text-red-800 dark:text-red-400 border border-red-250 font-sans">● فشل</span>
                                            @else
                                                <span class="status-badge inline-flex items-center gap-1 rounded-full bg-yellow-50 dark:bg-yellow-950/20 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:text-yellow-450 border border-yellow-250 font-sans">● {{ $event->status }}</span>
                                            @endif

                                            @if(!empty($event->last_error))
                                                <button
                                                    type="button"
                                                    class="text-xs text-red-500 hover:text-red-700 underline block mt-1 w-full text-center hover:cursor-pointer"
                                                    onclick="toggleError('inbox-err-{{ $event->id }}')"
                                                >
                                                    تفاصيل الخطأ
                                                </button>
                                            @endif
                                        </td>
                                        <td class="p-4 text-center font-mono text-xs">{{ $event->attempts }}</td>
                                        <td class="p-4 text-xs text-gray-650 dark:text-gray-400 font-mono">
                                            {{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}
                                        </td>
                                        <td class="p-4 text-center">
                                            <button
                                                type="button"
                                                class="secondary-button py-1 px-3 text-xs bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded flex items-center justify-center gap-1 m-auto ae-inbox-replay-btn"
                                                data-id="{{ $event->id }}"
                                            >
                                                <span class="icon-settings spinner-icon animate-spin-slow"></span>
                                                إعادة معالجة
                                            </button>
                                        </td>
                                    </tr>
                                    {{-- Error detail row --}}
                                    @if(!empty($event->last_error))
                                        <tr id="inbox-err-{{ $event->id }}" class="hidden bg-red-50/30 dark:bg-red-950/10">
                                            <td colspan="7" class="p-4 text-xs text-red-600 dark:text-red-400 border-l border-red-500">
                                                <div class="flex flex-col gap-1 pr-6 font-mono whitespace-pre-line text-right">
                                                    <strong>تفاصيل خطأ الوارد:</strong>
                                                    {{ $event->last_error }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                            لا توجد أحداث وارد خارجية مسجلة حالياً.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($inboxEvents->hasPages())
                        <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                            {!! $inboxEvents->appends(['active_tab' => 'events-tracker'])->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .animate-spin-slow {
                animation: spin 3s linear infinite;
            }
            .animate-spin-slow:not(.running) {
                animation: none;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function toggleError(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.toggle('hidden');
                }
            }

            (function() {
                // --- Tab State Management (Sprint 2) ---

                document.addEventListener('click', function(e) {
                    const tabBtn = e.target.closest('.ae-tab-btn');
                    if (tabBtn) {
                        e.preventDefault();
                        const target = tabBtn.getAttribute('data-tab');
                        setActiveTab(target);
                    }
                });

                function setActiveTab(targetTab) {
                    const tabs = document.querySelectorAll('.ae-tab-btn');
                    const contents = document.querySelectorAll('.ae-tab-content');

                    tabs.forEach(btn => {
                        if (btn.getAttribute('data-tab') === targetTab) {
                            btn.className = "ae-tab-btn py-3 text-sm font-bold text-amber-600 dark:text-amber-500 border-b-2 border-amber-600 dark:border-amber-500 focus:outline-none transition-all font-sans";
                        } else {
                            btn.className = "ae-tab-btn py-3 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 border-b-2 border-transparent focus:outline-none transition-all font-sans";
                        }
                    });

                    contents.forEach(content => {
                        if (content.id === `tab-content-${targetTab}`) {
                            content.classList.remove('hidden');
                            content.classList.add('flex');
                        } else {
                            content.classList.remove('flex');
                            content.classList.add('hidden');
                        }
                    });

                    // Update URL parameter without full page reload
                    const url = new URL(window.location);
                    url.searchParams.set('active_tab', targetTab);
                    window.history.pushState({}, '', url);
                }

                // Restore active tab state from URL or defaults
                const urlParams = new URLSearchParams(window.location.search);
                let initialTab = urlParams.get('active_tab') || 'imported-products';
                if (!urlParams.get('active_tab')) {
                    if (urlParams.has('runs_page')) {
                        initialTab = 'sync-runs';
                    } else if (urlParams.has('outbox_page') || urlParams.has('inbox_page')) {
                        initialTab = 'events-tracker';
                    }
                }
                setActiveTab(initialTab);

                // --- Replay Buttons Management (Sprint 2) ---
                document.addEventListener('click', function(e) {
                    // Outbox Replay Action
                    const outboxBtn = e.target.closest('.ae-outbox-replay-btn');
                    if (outboxBtn) {
                        e.preventDefault();
                        if (outboxBtn.disabled) return;
                        const id = outboxBtn.getAttribute('data-id');
                        triggerReplay(id, outboxBtn, 'outbox');
                        return;
                    }

                    // Inbox Replay Action
                    const inboxBtn = e.target.closest('.ae-inbox-replay-btn');
                    if (inboxBtn) {
                        e.preventDefault();
                        if (inboxBtn.disabled) return;
                        const id = inboxBtn.getAttribute('data-id');
                        triggerReplay(id, inboxBtn, 'inbox');
                        return;
                    }
                });

                function triggerReplay(id, btn, type) {
                    setRowBusy(btn, true);
                    const url = type === 'outbox' 
                        ? "{{ route('admin.dropshipping.sync.outbox.replay', ':id') }}".replace(':id', id)
                        : "{{ route('admin.dropshipping.sync.inbox.replay', ':id') }}".replace(':id', id);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert(data.message || 'تمت العملية بنجاح');
                        // Reload the page to display updated outbox/inbox list state
                        window.location.reload();
                    })
                    .catch(err => {
                        alert(err.message || 'فشلت العملية');
                    })
                    .finally(() => {
                        setRowBusy(btn, false);
                    });
                }

                // --- Sync Management Javascript (Imports & Bulk Sync) ---
                let bulkSyncActive = false;
                let cancelSync = false;

                function $(id) {
                    return document.getElementById(id);
                }

                document.addEventListener('click', function(e) {
                    // 1. Single product sync row button
                    const syncBtn = e.target.closest('.ae-row-sync-btn');
                    if (syncBtn) {
                        e.preventDefault();
                        if (syncBtn.disabled) return;
                        const id = syncBtn.getAttribute('data-id');
                        runSingleSync(id, syncBtn);
                        return;
                    }

                    // 2. Sync all products button
                    const syncAllBtn = e.target.closest('#ae-sync-all-btn');
                    if (syncAllBtn) {
                        e.preventDefault();
                        if (bulkSyncActive) return;
                        startBulkSync(syncAllBtn);
                        return;
                    }

                    // 3. Cancel bulk sync button
                    const cancelBulkBtn = e.target.closest('#ae-cancel-bulk-btn');
                    if (cancelBulkBtn) {
                        e.preventDefault();
                        if (bulkSyncActive) {
                            cancelSync = true;
                            cancelBulkBtn.disabled = true;
                        }
                        return;
                    }
                });

                function runSingleSync(id, btn) {
                    setRowBusy(btn, true);
                    const url = "{{ route('admin.dropshipping.sync.run_single', ':id') }}".replace(':id', id);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        updateRowStatus(id, true, data);
                    })
                    .catch(err => {
                        updateRowStatus(id, false, err);
                    })
                    .finally(() => {
                        setRowBusy(btn, false);
                    });
                }

                function setRowBusy(btn, busy) {
                    if (!btn) return;
                    btn.disabled = busy;
                    const spinner = btn.querySelector('.spinner-icon');
                    if (spinner) {
                        if (busy) {
                            spinner.classList.add('running');
                            spinner.style.animationPlayState = 'running';
                        } else {
                            spinner.classList.remove('running');
                            spinner.style.animationPlayState = 'paused';
                        }
                    }
                }

                function updateRowStatus(id, success, data) {
                    const row = $('import-row-' + id);
                    if (!row) return;

                    const badge = row.querySelector('.status-badge');
                    if (badge) {
                        if (success) {
                            badge.className = "status-badge inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 font-sans";
                            badge.innerHTML = "● مكتمل";
                        } else {
                            badge.className = "status-badge inline-flex items-center gap-1 rounded-full bg-red-50 dark:bg-red-950/20 px-2.5 py-1 text-xs font-semibold text-red-800 dark:text-red-400 border border-red-200 dark:border-red-900/50 font-sans";
                            badge.innerHTML = "● فشل";
                        }
                    }

                    const updatedEl = row.querySelector('.last-updated');
                    if (updatedEl && data && data.updated_at) {
                        updatedEl.textContent = data.updated_at;
                    }

                    let errRow = $('err-' + id);
                    if (!success && data && data.error) {
                        if (errRow) {
                            const errContainer = errRow.querySelector('div');
                            if (errContainer) {
                                errContainer.innerHTML = `<strong>رسالة الخطأ المسجلة:</strong><br>${data.error}`;
                            }
                            errRow.classList.remove('hidden');
                        } else {
                            const newErrRow = document.createElement('tr');
                            newErrRow.id = `err-${id}`;
                            newErrRow.className = "bg-red-50/30 dark:bg-red-950/10";
                            newErrRow.innerHTML = `
                                <td colspan="6" class="p-4 text-xs text-red-600 dark:text-red-400 border-l border-red-500">
                                    <div class="flex flex-col gap-1 pr-6 font-mono whitespace-pre-line text-right">
                                        <strong>رسالة الخطأ المسجلة:</strong>
                                        ${data.error}
                                    </div>
                                </td>
                            `;
                            row.parentNode.insertBefore(newErrRow, row.nextSibling);
                        }

                        let errorToggleBtn = badge.parentNode.querySelector('button');
                        if (!errorToggleBtn) {
                            errorToggleBtn = document.createElement('button');
                            errorToggleBtn.type = 'button';
                            errorToggleBtn.className = "text-xs text-red-500 hover:text-red-700 underline block mt-1 w-full text-center hover:cursor-pointer";
                            errorToggleBtn.textContent = "تفاصيل الخطأ";
                            errorToggleBtn.onclick = function() { toggleError(`err-${id}`); };
                            badge.parentNode.appendChild(errorToggleBtn);
                        }
                    } else if (success && errRow) {
                        errRow.remove();
                        const errorToggleBtn = badge.parentNode.querySelector('button');
                        if (errorToggleBtn) {
                            errorToggleBtn.remove();
                        }
                    }
                }

                function logBulk(msg) {
                    const progressLog = $('bulk-progress-log');
                    if (!progressLog) return;
                    const entry = document.createElement('div');
                    entry.className = "py-0.5 border-b border-gray-100 dark:border-gray-700/50 last:border-0 font-sans";
                    entry.textContent = `[${new Date().toLocaleTimeString('ar-SA')}] ${msg}`;
                    progressLog.appendChild(entry);
                    progressLog.scrollTop = progressLog.scrollHeight;
                }

                function updateProgressBar(percent, titleText) {
                    const progressBar = $('bulk-progress-bar');
                    const progressPercentage = $('bulk-progress-percentage');
                    const progressTitle = $('bulk-progress-title');
                    if (progressBar) progressBar.style.width = percent + '%';
                    if (progressPercentage) progressPercentage.textContent = percent + '%';
                    if (titleText && progressTitle) progressTitle.textContent = titleText;
                }

                function startBulkSync(syncAllBtn) {
                    bulkSyncActive = true;
                    cancelSync = false;

                    if (syncAllBtn) {
                        syncAllBtn.disabled = true;
                        syncAllBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                    
                    const cancelBulkBtn = $('ae-cancel-bulk-btn');
                    if (cancelBulkBtn) {
                        cancelBulkBtn.disabled = false;
                    }

                    const progressPanel = $('ae-bulk-progress-panel');
                    if (progressPanel) {
                        progressPanel.classList.remove('hidden');
                        progressPanel.classList.add('flex');
                    }
                    const progressLog = $('bulk-progress-log');
                    if (progressLog) progressLog.innerHTML = '';
                    updateProgressBar(0);

                    logBulk('🔄 جاري جلب قائمة المعرفات القابلة للمزامنة...');

                    fetch("{{ route('admin.dropshipping.sync.get_all_syncable') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        const ids = data.ids || [];

                        const totalCard = $('stat-total-count');
                        const successCard = $('stat-success-count');
                        const failedCard = $('stat-failed-count');
                        if (totalCard) totalCard.textContent = ids.length;
                        if (successCard) successCard.textContent = 0;
                        if (failedCard) failedCard.textContent = 0;

                        if (ids.length === 0) {
                            logBulk('ℹ️ لا توجد منتجات مستوردة للمزامنة.');
                            finishBulkSync('اكتمل: لا توجد منتجات صالحة للمزامنة');
                            return;
                        }

                        logBulk(`✓ تم العثور على ${ids.length} منتج(اً) جاهزاً للمزامنة.`);
                        processSequentialSync(ids, 0, 0, 0);
                    })
                    .catch(err => {
                        logBulk(`✖ خطأ أثناء استرداد المعرفات: ${err.message || err}`);
                        finishBulkSync('فشل المزامنة الجماعية');
                    });
                }

                function processSequentialSync(ids, index, successCount, failedCount) {
                    if (cancelSync) {
                        logBulk('🛑 تم إيقاف عملية المزامنة بواسطة المسؤول.');
                        finishBulkSync(`تم الإيقاف (ناجح: ${successCount}، فشل: ${failedCount})`);
                        return;
                    }

                    if (index >= ids.length) {
                        logBulk('✨ اكتملت مزامنة جميع المنتجات المستهدفة بنجاح.');
                        finishBulkSync(`اكتملت العملية (ناجح: ${successCount}، فشل: ${failedCount})`);
                        return;
                    }

                    const currentId = ids[index];
                    const progressNum = index + 1;
                    const totalNum = ids.length;
                    const percent = Math.round((index / totalNum) * 100);

                    updateProgressBar(percent, `جاري مزامنة المنتج رقم ${progressNum} من أصل ${totalNum}...`);
                    logBulk(`⏳ [${progressNum}/${totalNum}] جاري مزامنة السجل ID: ${currentId}...`);

                    const row = $('import-row-' + currentId);
                    const rowBtn = row ? row.querySelector('.ae-row-sync-btn') : null;
                    setRowBusy(rowBtn, true);

                    const url = "{{ route('admin.dropshipping.sync.run_single', ':id') }}".replace(':id', currentId);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(res => {
                        if (!res.ok) {
                            return res.json().then(err => { throw err; });
                        }
                        return res.json();
                    })
                    .then(data => {
                        successCount++;
                        updateRowStatus(currentId, true, data);
                        logBulk(`  ✓ نجحت المزامنة للسجل ID: ${currentId}.`);
                        incrementStatCount('stat-success-count');
                    })
                    .catch(err => {
                        failedCount++;
                        updateRowStatus(currentId, false, err);
                        logBulk(`  ✖ فشل المزامنة للسجل ID: ${currentId} - ${err.message || 'خطأ غير معروف'}`);
                        incrementStatCount('stat-failed-count');
                    })
                    .finally(() => {
                        setRowBusy(rowBtn, false);
                        setTimeout(function() {
                            processSequentialSync(ids, index + 1, successCount, failedCount);
                        }, 100);
                    });
                }

                function incrementStatCount(statId) {
                    const el = $(statId);
                    if (el) {
                        const current = parseInt(el.textContent) || 0;
                        el.textContent = current + 1;
                    }
                }

                function finishBulkSync(finalTitle) {
                    updateProgressBar(100, finalTitle);
                    bulkSyncActive = false;
                    cancelSync = false;
                    
                    const syncAllBtn = $('ae-sync-all-btn');
                    if (syncAllBtn) {
                        syncAllBtn.disabled = false;
                        syncAllBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                    
                    const cancelBulkBtn = $('ae-cancel-bulk-btn');
                    if (cancelBulkBtn) {
                        cancelBulkBtn.disabled = true;
                    }
                }
            })();
        </script>
    @endpush
</x-admin::layouts>
