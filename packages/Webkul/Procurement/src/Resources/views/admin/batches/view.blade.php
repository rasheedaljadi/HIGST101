<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.batches.batch-details', ['number' => $batch->batch_number]) }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.procurement.batches.index') }}" class="hover:text-blue-600">
                        {{ trans('procurement::app.batches.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $batch->batch_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ $batch->batch_number }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                @if ($batch->state === 'ready_for_review' && bouncer()->hasPermission('dropshipping.procurement_v2.batch_approve'))
                    <form action="{{ route('admin.procurement.batches.approve', $batch->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                            {{ trans('procurement::app.batches.approve') }}
                        </button>
                    </form>

                    <form action="{{ route('admin.procurement.batches.reject', $batch->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ trans('procurement::app.batches.confirm-reject') }}')">
                        @csrf
                        <input type="hidden" name="reason" value="Rejected by admin operator">
                        <button type="submit" class="secondary-button text-rose-600 border-rose-300 hover:bg-rose-50">
                            {{ trans('procurement::app.batches.reject') }}
                        </button>
                    </form>
                @endif

                @if ($batch->state === 'approved' && bouncer()->hasPermission('dropshipping.procurement_v2.submit'))
                    <form action="{{ route('admin.procurement.batches.submit', $batch->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-blue-600 hover:bg-blue-700">
                            {{ trans('procurement::app.batches.submit-to-aliexpress') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Batch Information Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.state') }}</span>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ trans("procurement::app.states.{$batch->state}") ?: $batch->state }}
                    </span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.batches.stores-count') }}</span>
                <div class="text-xl font-bold text-gray-900 dark:text-white mt-2">{{ $batch->supplierOrders->count() }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.batches.demands-count') }}</span>
                <div class="text-xl font-bold text-gray-900 dark:text-white mt-2">{{ $batch->demands->count() }}</div>
            </div>
            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.expected-cost') }}</span>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">${{ number_format((float) $batch->expected_total_cost, 2) }}</div>
                </div>
            @endif
        </div>

        {{-- Supplier Purchase Orders Section --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ trans('procurement::app.batches.split-supplier-pos') }} ({{ $batch->supplierOrders->count() }})
                </h2>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($batch->supplierOrders as $spo)
                    <div class="p-5 flex flex-col gap-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.procurement.supplier_orders.view', $spo->id) }}" class="text-base font-bold text-blue-600 hover:underline">
                                    {{ $spo->purchase_order_number }}
                                </a>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $spo->supplier_store_name ?: ($spo->supplier_store_id ?: 'AliExpress Store') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ trans("procurement::app.states.{$spo->state}") ?: $spo->state }}
                                </span>
                                @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                    <span class="font-bold text-gray-900 dark:text-white text-sm">
                                        ${{ number_format((float) $spo->expected_total, 2) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- PO Items Table --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs ltr:text-left rtl:text-right text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
                                <thead class="text-gray-700 dark:text-gray-300 uppercase bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="p-2.5 ltr:text-left rtl:text-right">{{ trans('procurement::app.supplier_orders.sku-product') }}</th>
                                        <th class="p-2.5 text-center">{{ trans('procurement::app.batches.qty-ordered') }}</th>
                                        <th class="p-2.5 text-center">{{ trans('procurement::app.batches.allocated-demands') }}</th>
                                        @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                            <th class="p-2.5 ltr:text-right rtl:text-left">{{ trans('procurement::app.batches.unit-cost') }}</th>
                                            <th class="p-2.5 ltr:text-right rtl:text-left">{{ trans('procurement::app.batches.total') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($spo->items as $item)
                                        <tr>
                                            <td class="p-2.5">
                                                <div class="font-semibold text-gray-900 dark:text-white">{{ $item->product?->name ?: $item->supplier_product_id }}</div>
                                                <div class="font-mono text-gray-500">{{ $item->supplier_sku_id }}</div>
                                            </td>
                                            <td class="p-2.5 text-center font-bold">{{ $item->qty_ordered }}</td>
                                            <td class="p-2.5 text-center">
                                                @foreach ($item->allocations as $alloc)
                                                    <span class="inline-block bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 rounded text-[11px] font-mono mr-1 mb-1">
                                                        {{ trans('procurement::app.datagrid.demand-id') }}: #{{ $alloc->procurement_demand_id }} ({{ $alloc->qty_allocated }})
                                                    </span>
                                                @endforeach
                                            </td>
                                            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                                <td class="p-2.5 ltr:text-right rtl:text-left">${{ number_format((float) $item->expected_unit_cost, 2) }}</td>
                                                <td class="p-2.5 ltr:text-right rtl:text-left font-bold text-gray-900 dark:text-white">${{ number_format((float) ($item->qty_ordered * $item->expected_unit_cost), 2) }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin::layouts>
