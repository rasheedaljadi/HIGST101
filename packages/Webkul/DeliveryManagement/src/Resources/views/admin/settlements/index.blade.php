<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.settlements.title') }}
    </x-slot>

    @php
        $activeGrid = request()->query('grid', 'settlements');
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
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('delivery::app.admin.settlements.title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ trans('delivery::app.admin.settlements.title') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ trans('delivery::app.admin.settlements.description') }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="document.getElementById('settlement-modal').classList.remove('hidden')" class="primary-button flex items-center gap-2">
                    <span class="icon-wallet text-xl"></span>
                    {{ trans('delivery::app.admin.settlements.process-settlement') }}
                </button>
            </div>
        </div>

        {{-- Financial Metric Cards --}}
        <div class="grid grid-cols-4 gap-4 max-xl:grid-cols-2 max-sm:grid-cols-1">
            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm border-r-4 border-r-emerald-500">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">إجمالي المبالغ المحصلة (COD)</span>
                <div class="text-xl font-bold text-emerald-600 mt-1">{{ number_format($metrics['total_collected_yer'], 2) }} YER</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm border-r-4 border-r-blue-500">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">إجمالي المبالغ المسواة والموردة</span>
                <div class="text-xl font-bold text-blue-600 mt-1">{{ number_format($metrics['total_settled_yer'], 2) }} YER</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm border-r-4 border-r-amber-500">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">العهد النقدية المعلقة مع المناديب</span>
                <div class="text-xl font-bold text-amber-600 mt-1">{{ number_format($metrics['unsettled_float_yer'], 2) }} YER</div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm border-r-4 border-r-rose-500">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">إجمالي الفروقات والعجز المالي</span>
                <div class="text-xl font-bold text-rose-600 mt-1">{{ number_format($metrics['total_discrepancy'], 2) }} YER</div>
            </div>
        </div>

        {{-- Switch Tabs (Settlements / Collections) --}}
        <div class="flex items-center gap-3 border-b pb-2 text-xs">
            <a href="{{ route('admin.delivery.settlements.index') }}" class="px-3 py-1.5 rounded font-bold {{ $activeGrid !== 'collections' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                {{ trans('delivery::app.admin.settlements.courier-settlements') }}
            </a>
            <a href="{{ route('admin.delivery.settlements.index', ['grid' => 'collections']) }}" class="px-3 py-1.5 rounded font-bold {{ $activeGrid === 'collections' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                {{ trans('delivery::app.admin.settlements.cod-collections') }}
            </a>
        </div>

        {{-- Real Bagisto DataGrid --}}
        @if($activeGrid === 'collections')
            <x-admin::datagrid :src="route('admin.delivery.settlements.index', ['grid' => 'collections'])" />
        @else
            <x-admin::datagrid :src="route('admin.delivery.settlements.index')" />
        @endif
    </div>

    {{-- Process Settlement Modal --}}
    <div id="settlement-modal" class="fixed inset-0 z-50 bg-black/50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-900 rounded-xl p-6 w-full max-w-md shadow-2xl border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">{{ trans('delivery::app.admin.settlements.process-settlement') }}</h3>

            <form action="{{ route('admin.delivery.settlements.process') }}" method="POST" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ trans('delivery::app.admin.couriers.title') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="delivery_boy_id" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent">
                        <option value="">-- اختر المندوب لتسوية عهدته --</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}">{{ $courier->name }} ({{ $courier->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        المبلغ المورد للصندوق فعلياً (YER) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" name="total_submitted_yer" required class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent" placeholder="0.00">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        ملاحظات التسوية:
                    </label>
                    <textarea name="notes" rows="2" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 bg-transparent" placeholder="أي ملاحظات مالية أو أسباب للعجز إن وجد..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('settlement-modal').classList.add('hidden')" class="secondary-button">إلغاء</button>
                    <button type="submit" class="primary-button">اعتماد وتوثيق التسوية</button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
