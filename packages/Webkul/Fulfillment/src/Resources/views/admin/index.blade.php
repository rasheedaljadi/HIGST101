<x-admin::layouts>
    <x-slot:title>
        {{ trans('fulfillment::app.admin.menu.fulfillment') }}
    </x-slot>

    @php
        $activeTab = request()->query('tab', 'purchase_orders');
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ trans('fulfillment::app.admin.menu.fulfillment') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    مراقبة وإدارة أوامر الشراء الخارجية للموردين وطلبات الموافقات الإدارية.
                </p>
            </div>
        </div>

        {{-- Alert Banners Section (Error / Critical Alerts) --}}
        @if(!empty($alerts))
            <div class="flex flex-col gap-3">
                @foreach($alerts as $alert)
                    <div class="p-4 rounded-lg border flex items-start justify-between gap-4 {{ $alert['severity'] === 'critical' ? 'bg-red-50 dark:bg-red-950/20 text-red-800 dark:text-red-400 border-red-200 dark:border-red-900/50 border-r-4 border-r-red-600' : 'bg-rose-50 dark:bg-rose-950/10 text-rose-800 dark:text-rose-400 border-rose-100 dark:border-rose-900/30 border-r-4 border-r-rose-500' }}">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl mt-0.5 {{ $alert['severity'] === 'critical' ? 'icon-cancel' : 'icon-settings' }}"></span>
                            <div class="flex flex-col">
                                <span class="font-bold text-sm capitalize">{{ $alert['severity'] }} Alert</span>
                                <p class="text-xs mt-0.5 leading-relaxed">{{ $alert['message'] }}</p>
                                <span class="text-[10px] text-gray-400 mt-1">وقت التنبيه: {{ $alert['timestamp'] }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.dropshipping.fulfillment.clear-alert', $alert['id']) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs underline">
                                تجاهل التنبيه
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Statistics / KPI Cards --}}
        <div class="grid grid-cols-4 gap-6 max-xl:grid-cols-2 max-sm:grid-cols-1">
            {{-- Success Rate KPI --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-emerald-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ trans('fulfillment::app.admin.dashboard.success-rate') }}
                    </span>
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ $kpis['successRate'] }}%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        {{ trans('fulfillment::app.admin.dashboard.success-rate-desc') }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <span class="icon-toast-done text-2xl"></span>
                </div>
            </div>

            {{-- Retry Rate KPI --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-amber-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ trans('fulfillment::app.admin.dashboard.retry-rate') }}
                    </span>
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">
                        {{ $kpis['retryRate'] }}%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        {{ trans('fulfillment::app.admin.dashboard.retry-rate-desc') }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <span class="icon-settings text-2xl"></span>
                </div>
            </div>

            {{-- Average Fulfillment Time KPI --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-blue-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ trans('fulfillment::app.admin.dashboard.avg-fulfillment-time') }}
                    </span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $kpis['avgTime'] }} {{ $kpis['avgTime'] > 60 ? 'hrs' : 'mins' }}
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        {{ trans('fulfillment::app.admin.dashboard.avg-fulfillment-desc') }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/30 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <span class="icon-dashboard text-2xl"></span>
                </div>
            </div>

            {{-- Provider Health KPI --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-indigo-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ trans('fulfillment::app.admin.dashboard.provider-health') }}
                    </span>
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">
                        {{ $kpis['health'] }}%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        {{ trans('fulfillment::app.admin.dashboard.provider-health-desc') }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/30 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <span class="icon-sales text-2xl"></span>
                </div>
            </div>
        </div>

        {{-- Immediate Counters Section --}}
        <div class="grid grid-cols-3 gap-6 max-md:grid-cols-1">
            <div class="flex items-center gap-4 p-4 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/50 rounded-lg">
                <span class="icon-settings text-3xl text-yellow-600 dark:text-yellow-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ trans('fulfillment::app.admin.dashboard.waiting-orders') }}</span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white">{{ $kpis['waiting'] }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/50 rounded-lg">
                <span class="icon-cancel text-3xl text-red-600 dark:text-red-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ trans('fulfillment::app.admin.dashboard.manual-review-orders') }}</span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white">{{ $kpis['needsReview'] }}</span>
                </div>
            </div>

            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <span class="icon-dashboard text-3xl text-gray-600 dark:text-gray-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ trans('fulfillment::app.admin.dashboard.queue-backlog') }}</span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white">{{ $kpis['backlog'] }}</span>
                </div>
            </div>
        </div>

        {{-- Tab Section --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <div class="flex gap-6">
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => request()->query('po_state', 'all')]) }}"
                    class="pb-4 text-sm font-semibold transition-all {{ $activeTab === 'purchase_orders' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    أوامر الشراء (Purchase Orders)
                </a>

                @if(config('fulfillment.approval_workflow.enabled', false))
                    <a
                        href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'approval_requests']) }}"
                        class="pb-4 text-sm font-semibold transition-all {{ $activeTab === 'approval_requests' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
                    >
                        طلبات الموافقات (Approval Requests)
                    </a>
                @endif
            </div>
        </div>

        {{-- Sub-Tab Section for Purchase Orders --}}
        @if ($activeTab === 'purchase_orders')
            @php
                $activePoState = request()->query('po_state', 'all');
            @endphp
            <div class="flex flex-wrap gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-800">
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'all']) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 {{ $activePoState === 'all' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900' }}"
                >
                    عرض الكل ({{ $poCounts['all'] }})
                </a>
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'awaiting_payment_to_supplier']) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 {{ $activePoState === 'awaiting_payment_to_supplier' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900' }}"
                >
                    قيد انتظار الدفع ({{ $poCounts['awaiting_payment'] }})
                </a>
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'in_progress']) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 {{ $activePoState === 'in_progress' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900' }}"
                >
                    جاري الإجراء ({{ $poCounts['in_progress'] }})
                </a>
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'submitted']) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 {{ $activePoState === 'submitted' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900' }}"
                >
                    تم الإجراء ({{ $poCounts['submitted'] }})
                </a>
                <a
                    href="{{ route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'completed']) }}"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 {{ $activePoState === 'completed' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900' }}"
                >
                    تم الاكمال ({{ $poCounts['completed'] }})
                </a>
            </div>
        @endif

        {{-- DataGrids Panel --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm p-2">
            @if ($activeTab === 'purchase_orders')
                <x-admin::datagrid :src="route('admin.dropshipping.fulfillment.index', ['po_state' => request()->query('po_state', 'all')])"></x-admin::datagrid>
            @elseif ($activeTab === 'approval_requests')
                <x-admin::datagrid :src="route('admin.dropshipping.fulfillment.index', ['grid' => 'approvals'])"></x-admin::datagrid>
            @endif
        </div>
    </div>
</x-admin::layouts>
