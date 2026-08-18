<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.dashboard.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('inventory::app.admin.menu.inventory') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.dashboard.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('inventory::app.admin.dashboard.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('inventory::app.admin.dashboard.description') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.transfers.create') }}" class="secondary-button flex items-center gap-2">
                    <span class="icon-ship text-xl"></span>
                    {{ trans('inventory::app.admin.transfers.create-title') }}
                </a>

                <a href="{{ route('admin.inventory.receipts.create') }}" class="primary-button flex items-center gap-2">
                    <span class="icon-done text-xl"></span>
                    {{ trans('inventory::app.admin.receipts.create-title') }}
                </a>
            </div>
        </div>

        {{-- Virtual Catalog Projection Warning Box (Isolated from Local Stock) --}}
        <div class="p-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300">
                    <span class="icon-attribute text-xl"></span>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-bold text-amber-900 dark:text-amber-200">
                        {{ trans('inventory::app.admin.dashboard.virtual-catalog') }}: {{ number_format($stats['virtual_projection']) }} وحدة
                    </span>
                    <span class="text-xs text-amber-700 dark:text-amber-400">
                        {{ trans('inventory::app.admin.dashboard.virtual-catalog-desc') }}
                    </span>
                </div>
            </div>

            <a href="{{ route('admin.inventory.products.index') }}" class="text-xs font-semibold text-amber-800 dark:text-amber-300 hover:underline">
                استعراض بطاقات المنتجات والإسقاط ←
            </a>
        </div>

        {{-- KPI Cards Grid (8 Operational Indicators) --}}
        <div class="grid grid-cols-4 gap-4 max-xl:grid-cols-2 max-sm:grid-cols-1">
            {{-- 1. Total Salable Stock (YE) --}}
            <a href="{{ route('admin.inventory.products.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-emerald-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.total-salable') }}</span>
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($stats['total_salable']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">جاهز للبيع والتسليم في اليمن</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600">
                    <span class="icon-product text-xl"></span>
                </div>
            </a>

            {{-- 2. Yemen Internal Stock --}}
            <a href="{{ route('admin.inventory.sources.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-blue-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.internal-stock') }}</span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ number_format($stats['internal_ye']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">مستودع صنعاء للبضاعة الجاهزة</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600">
                    <span class="icon-sales text-xl"></span>
                </div>
            </a>

            {{-- 3. Yemen Dropship Stock --}}
            <a href="{{ route('admin.inventory.sources.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-indigo-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.dropship-stock') }}</span>
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($stats['dropship_ye']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">مركز توزيع الدروبشوبنج (صنعاء)</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600">
                    <span class="icon-ship text-xl"></span>
                </div>
            </a>

            {{-- 4. In-Transit Transfers --}}
            <a href="{{ route('admin.inventory.transfers.index', ['status' => 'in_transit']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-cyan-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.in-transit') }}</span>
                    <span class="text-2xl font-bold text-cyan-600 dark:text-cyan-400 mt-1">{{ number_format($stats['in_transit_qty']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">{{ $stats['in_transit_count'] }} مانيفست نقل نشط</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-cyan-50 dark:bg-cyan-950/50 flex items-center justify-center text-cyan-600">
                    <span class="icon-configuration text-xl"></span>
                </div>
            </a>

            {{-- 5. Quarantine Stock --}}
            <a href="{{ route('admin.inventory.quarantine.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-rose-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.quarantine') }}</span>
                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ number_format($stats['quarantine_total']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">SA: {{ $stats['quarantine_sa'] }} | YE: {{ $stats['quarantine_ye'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600">
                    <span class="icon-cancel text-xl"></span>
                </div>
            </a>

            {{-- 6. Allocated for Orders --}}
            <a href="{{ route('admin.inventory.reports.index', ['report_type' => 'allocations']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-amber-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.allocated') }}</span>
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['allocated_total']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">محجوز لطلبات قيد التجهيز والتسليم</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600">
                    <span class="icon-cart text-xl"></span>
                </div>
            </a>

            {{-- 7. Receipt Discrepancies --}}
            <a href="{{ route('admin.inventory.receipts.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-purple-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.discrepancies') }}</span>
                    <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($stats['discrepancies_total']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">تلف: {{ $stats['damaged_total'] }} | نقص: {{ $stats['missing_total'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-950/50 flex items-center justify-center text-purple-600">
                    <span class="icon-information text-xl"></span>
                </div>
            </a>

            {{-- 8. Stalled / Action Needed --}}
            <a href="{{ route('admin.inventory.quarantine.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-red-600 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('inventory::app.admin.dashboard.stalled') }}</span>
                    <span class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($stats['stalled_total']) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">مانيفستات وفحوصات تتطلب مراجعة</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-950/50 flex items-center justify-center text-red-600">
                    <span class="icon-warning text-xl"></span>
                </div>
            </a>
        </div>

        {{-- Middle Section: Breakdown & Latest Movements --}}
        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Distribution Breakdown Card --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span class="icon-dashboard text-lg text-blue-600"></span>
                    {{ trans('inventory::app.admin.dashboard.status-breakdown') }}
                </h2>

                <div class="flex flex-col gap-3">
                    @php
                        $grandPhysical = $stats['internal_ye'] + $stats['dropship_ye'] + $stats['staging_sa'] + $stats['in_transit_qty'] + $stats['quarantine_total'];
                    @endphp

                    @foreach($distribution as $label => $qty)
                        @php
                            $percent = $grandPhysical > 0 ? round(($qty / $grandPhysical) * 100, 1) : 0;
                        @endphp
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($qty) }} ({{ $percent }}%)</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Audited Movements Table --}}
            <div class="col-span-2 p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="icon-history text-lg text-emerald-600"></span>
                        {{ trans('inventory::app.admin.dashboard.latest-movements') }}
                    </h2>
                    <a href="{{ route('admin.inventory.movements.index') }}" class="text-xs text-blue-600 hover:underline font-semibold">
                        عرض سجل الحركات الكامل ←
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">النوع</th>
                                <th class="p-2.5">SKU</th>
                                <th class="p-2.5">التغير</th>
                                <th class="p-2.5">المسار</th>
                                <th class="p-2.5">المنفذ</th>
                                <th class="p-2.5">الوقت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recentMovements as $movement)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                            {{ trans("inventory::app.admin.movements.{$movement->movement_type}") ?: $movement->movement_type }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 font-bold text-gray-900 dark:text-white">
                                        {{ $movement->sku }}
                                    </td>
                                    <td class="p-2.5 font-bold {{ $movement->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $movement->quantity >= 0 ? '+'.$movement->quantity : $movement->quantity }}
                                    </td>
                                    <td class="p-2.5 text-gray-500">
                                        {{ $movement->targetInventorySource?->name ?: $movement->sourceInventorySource?->name ?: '-' }}
                                    </td>
                                    <td class="p-2.5 text-gray-500">
                                        {{ $movement->actor?->name ?: $movement->actor_type }}
                                    </td>
                                    <td class="p-2.5 text-gray-400">
                                        {{ core()->formatDate($movement->created_at, 'Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-400">لا توجد حركات مخزنية مسجلة حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
