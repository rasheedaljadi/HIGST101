<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.dashboard.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>{{ trans('delivery::app.admin.menu.delivery-management') }}</span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.dashboard.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.dashboard.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('delivery::app.admin.dashboard.description') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.delivery.assignments.index') }}" class="primary-button flex items-center gap-2">
                    <span class="icon-shipment text-xl"></span>
                    {{ trans('delivery::app.admin.assignments.title') }}
                </a>
            </div>
        </div>

        {{-- KPI Cards Grid (Clickable) --}}
        <div class="grid grid-cols-4 gap-4 max-xl:grid-cols-2 max-sm:grid-cols-1">
            {{-- Ready for Assignment --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'ready_for_assignment']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-yellow-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.ready-for-assign') }}</span>
                    <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['ready_for_assignment'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">بانتظار الإسناد للمندوب</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-yellow-50 dark:bg-yellow-950/50 flex items-center justify-center text-yellow-600">
                    <span class="icon-pending text-xl"></span>
                </div>
            </a>

            {{-- Assigned --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'assigned']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-blue-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.assigned') }}</span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['assigned'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">بانتظار تسليم المخزون (Handoff)</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600">
                    <span class="icon-sales text-xl"></span>
                </div>
            </a>

            {{-- Out for Delivery / Picked Up --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'out_for_delivery']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-indigo-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.out-for-delivery') }}</span>
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $stats['out_for_delivery'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">قيد التوصيل الميداني للعميل</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600">
                    <span class="icon-shipment text-xl"></span>
                </div>
            </a>

            {{-- Arrived at Point --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'arrived_at_point']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-cyan-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.arrived-at-point') }}</span>
                    <span class="text-2xl font-bold text-cyan-600 dark:text-cyan-400 mt-1">{{ $stats['arrived_at_point'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">داخل مراكز ونقاط الاستلام</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-cyan-50 dark:bg-cyan-950/50 flex items-center justify-center text-cyan-600">
                    <span class="icon-dashboard text-xl"></span>
                </div>
            </a>

            {{-- Delivered Today --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'delivered']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-emerald-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.delivered-today') }}</span>
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['delivered_today'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">إجمالي المكتمل: {{ $stats['delivered_total'] }}</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600">
                    <span class="icon-done text-xl"></span>
                </div>
            </a>

            {{-- Failed / Retry --}}
            <a href="{{ route('admin.delivery.failures.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-rose-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.failed') }} / {{ trans('delivery::app.admin.dashboard.retry-scheduled') }}</span>
                    <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">{{ $stats['delivery_failed'] }} / {{ $stats['retry_scheduled'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">استنفد المحاولات أو مجدول</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600">
                    <span class="icon-cancel text-xl"></span>
                </div>
            </a>

            {{-- Returned to Hayest --}}
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'returned_to_hayest']) }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-gray-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.returned') }}</span>
                    <span class="text-2xl font-bold text-gray-700 dark:text-gray-300 mt-1">{{ $stats['returned_to_hayest'] }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">استُعيد للمستودع المركزي</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600">
                    <span class="icon-refresh text-xl"></span>
                </div>
            </a>

            {{-- COD Collected Today --}}
            <a href="{{ route('admin.delivery.settlements.index') }}" class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-r-4 border-r-amber-500 flex items-center justify-between">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ trans('delivery::app.admin.dashboard.cod-collected-today') }}</span>
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ number_format((float) $stats['cod_collected_today'], 2) }}</span>
                    <span class="text-[10px] text-gray-400 mt-0.5">YER (تسويات معلقة: {{ $stats['pending_settlements'] }})</span>
                </div>
                <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600">
                    <span class="icon-wallet text-xl"></span>
                </div>
            </a>
        </div>

        {{-- Middle Section: Status Breakdown & Latest Operations --}}
        <div class="grid grid-cols-3 gap-6 max-lg:grid-cols-1">
            {{-- Status Distribution Card --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span class="icon-dashboard text-lg text-blue-600"></span>
                    {{ trans('delivery::app.admin.dashboard.status-distribution') }}
                </h2>

                <div class="flex flex-col gap-3">
                    @foreach($statusDistribution as $label => $count)
                        @php
                            $total = $stats['delivered_total'] + $stats['total_active'] + $stats['delivery_failed'] + $stats['retry_scheduled'] + $stats['returned_to_hayest'] + $stats['ready_for_assignment'];
                            $percent = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                        @endphp
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $count }} ({{ $percent }}%)</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Assignments List --}}
            <div class="col-span-2 p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="icon-shipment text-lg text-emerald-600"></span>
                        {{ trans('delivery::app.admin.dashboard.recent-assignments') }}
                    </h2>
                    <a href="{{ route('admin.delivery.assignments.index') }}" class="text-xs text-blue-600 hover:underline font-semibold">
                        عرض الكل ←
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b">
                            <tr>
                                <th class="p-2.5">المهمة / الطلب</th>
                                <th class="p-2.5">النوع</th>
                                <th class="p-2.5">المندوب / النقطة</th>
                                <th class="p-2.5">الحالة</th>
                                <th class="p-2.5">الوقت</th>
                                <th class="p-2.5 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recentAssignments as $assignment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="p-2.5 font-bold text-blue-600">
                                        #{{ $assignment->id }} ({{ $assignment->order?->increment_id }})
                                    </td>
                                    <td class="p-2.5">
                                        {{ $assignment->delivery_type === 'home_delivery' ? 'توصيل منزلي' : 'نقطة استلام' }}
                                    </td>
                                    <td class="p-2.5 font-medium">
                                        {{ $assignment->deliveryBoy?->name ?: $assignment->deliveryPoint?->name ?: 'غير مسند' }}
                                    </td>
                                    <td class="p-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-800">
                                            {{ trans("delivery::app.admin.states.{$assignment->status}") }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 text-gray-400">
                                        {{ core()->formatDate($assignment->created_at, 'Y-m-d H:i') }}
                                    </td>
                                    <td class="p-2.5 text-center">
                                        <a href="{{ route('admin.delivery.assignments.show', $assignment->id) }}" class="text-blue-600 hover:underline">
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-400">لا توجد مهام تسليم مسجلة حالياً.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts>
