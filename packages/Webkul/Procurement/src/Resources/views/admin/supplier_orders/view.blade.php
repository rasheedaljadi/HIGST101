<x-admin::layouts>
    <x-slot:title>
        {{ $order->purchase_order_number }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.procurement.supplier_orders.index') }}" class="hover:text-blue-600">
                        {{ trans('procurement::app.supplier_orders.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ $order->purchase_order_number }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    {{ $order->purchase_order_number }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $order->supplier_store_name ?: ($order->supplier_store_id ?: 'AliExpress Store') }} &bull; Batch: #{{ $order->batch?->batch_number }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if (in_array($order->state, ['awaiting_manual_payment', 'submitted', 'payment_declared']) && bouncer()->hasPermission('dropshipping.procurement_v2.payment_confirm'))
                    <button type="button" onclick="document.getElementById('manual-payment-modal').classList.remove('hidden')" class="primary-button bg-purple-600 hover:bg-purple-700">
                        {{ trans('procurement::app.manual_payments.declare-payment') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Top Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.state') }}</span>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ trans("procurement::app.states.{$order->state}") ?: $order->state }}
                    </span>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.payment-state') }}</span>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        {{ $order->payment_state }}
                    </span>
                </div>
            </div>
            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.expected-cost') }}</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format((float) $order->expected_total, 2) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.actual-cost') }}</span>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">${{ number_format((float) $order->actual_total, 2) }}</div>
                </div>
            @endif
        </div>

        {{-- Order Items Table --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ trans('procurement::app.supplier_orders.items-title') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-4">SKU / Product</th>
                            <th class="p-4 text-center">Ordered</th>
                            <th class="p-4 text-center">Received Good</th>
                            <th class="p-4 text-center">Damaged</th>
                            <th class="p-4 text-center">Missing</th>
                            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                <th class="p-4 text-right">Expected Cost</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="p-4">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $item->product?->name ?: $item->supplier_product_id }}</div>
                                    <div class="font-mono text-xs text-gray-500">{{ $item->supplier_sku_id }}</div>
                                </td>
                                <td class="p-4 text-center font-bold text-gray-900 dark:text-white">{{ $item->qty_ordered }}</td>
                                <td class="p-4 text-center text-emerald-600 font-semibold">{{ $item->qty_received_good }}</td>
                                <td class="p-4 text-center text-rose-600 font-semibold">{{ $item->qty_damaged }}</td>
                                <td class="p-4 text-center text-amber-600 font-semibold">{{ $item->qty_missing }}</td>
                                @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                    <td class="p-4 text-right font-semibold">${{ number_format((float) $item->expected_unit_cost, 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Inbound Receipt Section --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ trans('procurement::app.supplier_orders.receive-goods') }}</h2>
            </div>
            <form action="{{ route('admin.procurement.supplier_orders.receive', $order->id) }}" method="POST" class="p-5 flex flex-col gap-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ trans('procurement::app.supplier_orders.destination-warehouse') }}</label>
                        <select name="target_source" class="custom-select w-full rounded-lg border-gray-300">
                            @foreach ($inventorySources as $src)
                                <option value="{{ $src->code }}" {{ $src->code === 'hayest_dropship_sa' ? 'selected' : '' }}>
                                    {{ $src->name }} ({{ $src->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
                        <thead class="text-gray-700 dark:text-gray-300 uppercase bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="p-3">SKU</th>
                                <th class="p-3 text-center">Remaining to Receive</th>
                                <th class="p-3 text-center">Receive Good</th>
                                <th class="p-3 text-center">Damaged</th>
                                <th class="p-3 text-center">Missing</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($order->items as $idx => $item)
                                @php $remaining = max(0, $item->qty_ordered - $item->qty_received_good); @endphp
                                <tr>
                                    <td class="p-3 font-mono">
                                        <input type="hidden" name="lines[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                        {{ $item->supplier_sku_id }}
                                    </td>
                                    <td class="p-3 text-center font-bold">{{ $remaining }}</td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_good]" value="{{ $remaining }}" min="0" max="{{ $remaining }}" class="w-20 rounded border-gray-300 text-center py-1">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_damaged]" value="0" min="0" class="w-20 rounded border-gray-300 text-center py-1">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_missing]" value="0" min="0" class="w-20 rounded border-gray-300 text-center py-1">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                        {{ trans('procurement::app.supplier_orders.confirm-receipt') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manual Payment Modal --}}
    <div id="manual-payment-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ trans('procurement::app.manual_payments.declare-payment') }}</h3>
                <button type="button" onclick="document.getElementById('manual-payment-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form action="{{ route('admin.procurement.manual_payments.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <input type="hidden" name="supplier_purchase_order_id" value="{{ $order->id }}">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">{{ trans('procurement::app.manual_payments.external-reference') }}</label>
                    <input type="text" name="external_reference" required class="custom-input w-full rounded-lg border-gray-300" placeholder="e.g. AliExpress Order ID / Payment Ref">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">{{ trans('procurement::app.manual_payments.declared-amount-usd') }}</label>
                    <input type="number" step="0.01" name="declared_total" value="{{ $order->expected_total }}" required class="custom-input w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">{{ trans('procurement::app.general.notes') }}</label>
                    <textarea name="notes" rows="2" class="custom-textarea w-full rounded-lg border-gray-300" placeholder="Optional audit notes"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" onclick="document.getElementById('manual-payment-modal').classList.add('hidden')" class="secondary-button">{{ trans('procurement::app.general.cancel') }}</button>
                    <button type="submit" class="primary-button bg-purple-600 hover:bg-purple-700">{{ trans('procurement::app.manual_payments.confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
