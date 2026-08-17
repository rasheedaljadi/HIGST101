<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.failures.title') }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.delivery.dashboard.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.menu.delivery-management') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.failures.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.failures.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('delivery::app.admin.failures.description') }}
                </p>
            </div>
        </div>

        {{-- Urgent Boxes: Exhausted Attempts (Requires Supervisor Decision) --}}
        @if($exhaustedAssignments->isNotEmpty())
            <div class="p-5 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/50 rounded-lg flex flex-col gap-3">
                <h2 class="text-sm font-bold text-rose-800 dark:text-rose-400 flex items-center gap-2">
                    <span class="icon-cancel text-lg"></span>
                    طلبات استنفدت الحد الأقصى للمحاولات (3/3) - بانتظار قرار المشرف ({{ $exhaustedAssignments->count() }} طلب)
                </h2>

                <div class="grid grid-cols-3 gap-3 max-md:grid-cols-1">
                    @foreach($exhaustedAssignments as $assignment)
                        <div class="p-3 bg-white dark:bg-gray-900 rounded border border-rose-200 text-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between font-bold">
                                    <span>مهمة #{{ $assignment->id }}</span>
                                    <span class="text-rose-600">طلب #{{ $assignment->order?->increment_id }}</span>
                                </div>
                                <div class="text-gray-600 dark:text-gray-300 mt-1">
                                    العميل: {{ $assignment->order?->customer_first_name }} {{ $assignment->order?->customer_last_name }}
                                </div>
                                <div class="text-gray-400 text-[10px] mt-0.5">
                                    المندوب: {{ $assignment->deliveryBoy?->name ?: 'غير محدد' }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-2 border-t">
                                <a href="{{ route('admin.delivery.assignments.show', $assignment->id) }}" class="text-blue-600 hover:underline font-semibold">
                                    فتح التفاصيل والاعتماد ←
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Real Bagisto DataGrid for Attempt Logs --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm">
            <h2 class="text-base font-bold text-gray-800 dark:text-white mb-4">
                سجل كافة محاولات التوصيل (Attempt Logs)
            </h2>

            <x-admin::datagrid :src="route('admin.delivery.failures.index')" />
        </div>
    </div>
</x-admin::layouts>
