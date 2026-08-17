<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.assignments.title') }}
    </x-slot>

    @php
        $currentStatus = request()->query('status', 'all');
    @endphp

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.dashboard.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.menu.delivery-management') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.assignments.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.assignments.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('delivery::app.admin.assignments.description') }}
                </p>
            </div>
        </div>

        {{-- Status Tabs Filter --}}
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2 overflow-x-auto text-xs">
            <a href="{{ route('admin.delivery.assignments.index') }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                الكل ({{ $statusCounts['all'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'ready_for_assignment']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'ready_for_assignment' ? 'bg-yellow-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                جاهز للإسناد ({{ $statusCounts['ready_for_assignment'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'assigned']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'assigned' ? 'bg-blue-500 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                مسند ({{ $statusCounts['assigned'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'picked_up']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'picked_up' ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                مستلم من المستودع ({{ $statusCounts['picked_up'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'out_for_delivery']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'out_for_delivery' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                خرج للتوصيل ({{ $statusCounts['out_for_delivery'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'arrived_at_point']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'arrived_at_point' ? 'bg-cyan-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                وصل لنقطة التسليم ({{ $statusCounts['arrived_at_point'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'delivered']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'delivered' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                تم التسليم ({{ $statusCounts['delivered'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'delivery_failed']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'delivery_failed' ? 'bg-rose-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                فشل التسليم ({{ $statusCounts['delivery_failed'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'retry_scheduled']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'retry_scheduled' ? 'bg-orange-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                إعادة المحاولة ({{ $statusCounts['retry_scheduled'] }})
            </a>
            <a href="{{ route('admin.delivery.assignments.index', ['status' => 'returned_to_hayest']) }}" class="px-3 py-1.5 rounded-lg font-medium transition-all {{ $currentStatus === 'returned_to_hayest' ? 'bg-gray-700 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                المرتجعة للمركزي ({{ $statusCounts['returned_to_hayest'] }})
            </a>
        </div>

        {{-- Real Bagisto DataGrid --}}
        <x-admin::datagrid :src="route('admin.delivery.assignments.index', request()->query())" />
    </div>
</x-admin::layouts>
