<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.batches.create-batch') }}
    </x-slot>

    <form action="{{ route('admin.procurement.batches.store') }}" method="POST" id="create-batch-form">
        @csrf

        <div class="flex flex-col gap-6">
            {{-- Header Section --}}
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <a href="{{ route('admin.procurement.batches.index') }}" class="hover:text-blue-600">
                            {{ trans('procurement::app.batches.title') }}
                        </a>
                        <span>/</span>
                        <span class="text-gray-800 dark:text-white font-medium">{{ trans('procurement::app.batches.create-batch') }}</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                        {{ trans('procurement::app.batches.create-batch') }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ trans('procurement::app.batches.select-demands-desc') }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.procurement.batches.index') }}" class="secondary-button">
                        {{ trans('procurement::app.general.cancel') }}
                    </a>
                    <button type="submit" class="primary-button flex items-center gap-2" id="submit-batch-btn">
                        <span class="icon-save text-lg"></span>
                        {{ trans('procurement::app.batches.create-and-split') }}
                    </button>
                </div>
            </div>

            {{-- Open Demands Selection Table --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" onclick="toggleSelectAll(this)">
                            <label for="select-all" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                                {{ trans('procurement::app.batches.select-all') }} (<span id="selected-count">0</span> / {{ $openDemands->count() }})
                            </label>
                        </div>

                        @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                            <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-semibold">
                                <span>إجمالي التكلفة التقديرية المحددة:</span>
                                <span class="font-bold text-sm" id="selected-total-cost">$0.00</span>
                            </div>
                        @endif
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        {{ trans('procurement::app.general.currency') }}: <span class="font-bold text-gray-900 dark:text-white">USD</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-4 w-10"></th>
                                <th class="p-4">{{ trans('procurement::app.datagrid.demand-id') }}</th>
                                <th class="p-4">{{ trans('procurement::app.datagrid.order-id') }}</th>
                                <th class="p-4">{{ trans('procurement::app.datagrid.product-name') }}</th>
                                <th class="p-4">{{ trans('procurement::app.datagrid.supplier-store') }}</th>
                                <th class="p-4">{{ trans('procurement::app.datagrid.supplier-sku') }}</th>
                                <th class="p-4 text-center">{{ trans('procurement::app.datagrid.supplier-stock') }}</th>
                                <th class="p-4 text-center">{{ trans('procurement::app.datagrid.deficit-qty') }}</th>
                                @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                    <th class="p-4 text-right">{{ trans('procurement::app.datagrid.unit-cost') }}</th>
                                    <th class="p-4 text-right">{{ trans('procurement::app.datagrid.shipping-fee') }}</th>
                                    <th class="p-4 text-right">{{ trans('procurement::app.datagrid.total-cost-with-shipping') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($openDemands as $demand)
                                @php
                                    $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
                                    $deficit = (int) $demand->remaining_unbatched_qty;
                                    $itemsCost = $deficit * $unitCost;

                                    $import = \App\Models\AliExpressProductImport::where('id', $demand->source_snapshot['import_id'] ?? null)
                                        ->orWhere('aliexpress_product_id', $demand->supplier_product_id)
                                        ->orWhere('product_id', $demand->product_id)
                                        ->latest('id')
                                        ->first();

                                    $skuStock = null;
                                    if ($import && ! empty($import->payload_snapshot['variants'])) {
                                        foreach ($import->payload_snapshot['variants'] as $v) {
                                            $sId = (string) ($v['sku_id'] ?? $v['id'] ?? '');
                                            if ($sId == $demand->supplier_sku_id || count($import->payload_snapshot['variants']) === 1) {
                                                $skuStock = isset($v['stock']) || isset($v['quantity']) || isset($v['sku_stock'])
                                                    ? (int) ($v['stock'] ?? $v['quantity'] ?? $v['sku_stock'])
                                                    : 0;
                                                break;
                                            }
                                        }
                                    }

                                    if ($skuStock === null && ! empty($demand->source_snapshot['stock'])) {
                                        $skuStock = (int) $demand->source_snapshot['stock'];
                                    }

                                    $isOutOfStock = ($skuStock !== null && $skuStock <= 0);

                                    $isChoice = $import && (
                                        $import->is_choice 
                                        || stripos((string) $import->shipping_company, 'choice') !== false 
                                        || (float) $import->base_shipping_cost == 0
                                    );
                                    $shippingFee = ($isChoice || ! $import) ? 0.0 : (float) ($import->base_shipping_cost ?? 0.0);
                                    $lineGrandTotal = $itemsCost + $shippingFee;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition {{ $isOutOfStock ? 'bg-rose-50/40 dark:bg-rose-950/20' : '' }}">
                                    <td class="p-4">
                                        <input 
                                            type="checkbox" 
                                            name="demand_ids[]" 
                                            value="{{ $demand->id }}" 
                                            data-grand-total="{{ $lineGrandTotal }}"
                                            {{ $isOutOfStock ? 'disabled' : '' }}
                                            class="demand-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 {{ $isOutOfStock ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer' }}" 
                                            onchange="updateSelectionSummary()"
                                            title="{{ $isOutOfStock ? 'لا يمكن تحديد هذا الصنف لنفاد المخزون لدى المورد' : '' }}"
                                        >
                                    </td>
                                    <td class="p-4 font-semibold text-gray-900 dark:text-gray-100">#{{ $demand->id }}</td>
                                    <td class="p-4">
                                        <span class="font-medium text-blue-600">#{{ $demand->order?->increment_id ?: $demand->order_id }}</span>
                                    </td>
                                    <td class="p-4">
                                        @php
                                            $pName = $demand->orderItem?->name ?: $demand->product?->name ?: 'منتج بدون اسم';
                                            $additional = $demand->orderItem?->additional;
                                            $attrBadges = [];
                                            if (! empty($additional['attributes']) && is_array($additional['attributes'])) {
                                                foreach ($additional['attributes'] as $attr) {
                                                    $attrName = $attr['attribute_name'] ?? '';
                                                    $optLabel = $attr['option_label'] ?? '';
                                                    if ($optLabel) {
                                                        $attrBadges[] = [
                                                            'name' => $attrName,
                                                            'label' => $optLabel,
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="flex flex-col max-w-[260px]">
                                            <span class="font-semibold text-gray-900 dark:text-white text-xs leading-snug line-clamp-2" title="{{ $pName }}">
                                                {{ $pName }}
                                            </span>
                                            @if (! empty($attrBadges))
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @foreach ($attrBadges as $b)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                            @if ($b['name'])
                                                                <span class="text-blue-600 dark:text-blue-400 font-normal">{{ $b['name'] }}:</span>
                                                            @endif
                                                            <span class="font-bold">{{ $b['label'] }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="p-4">{{ $demand->supplier_store_name ?: ($demand->supplier_store_id ?: 'AliExpress Store') }}</td>
                                    <td class="p-4 font-mono text-xs">{{ $demand->supplier_sku_id }}</td>
                                    <td class="p-4 text-center">
                                        @if ($skuStock === null)
                                            <span class="text-gray-400 font-mono">-</span>
                                        @elseif ($skuStock <= 0)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800">
                                                <i class="icon-cancel text-xs"></i> غير متوفر (0)
                                            </span>
                                        @elseif ($skuStock < 5)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800 font-mono">
                                                {{ $skuStock }} قطعة
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800 font-mono">
                                                {{ $skuStock }} قطعة
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center font-bold text-gray-900 dark:text-white">{{ $deficit }}</td>
                                    @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                        <td class="p-4 text-right font-mono">${{ number_format($unitCost, 2) }}</td>
                                        <td class="p-4 text-right">
                                            @if ($isChoice)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                                                    Choice
                                                </span>
                                                <span class="text-xs text-gray-500 font-mono ml-1">$0.00</span>
                                            @elseif ($shippingFee > 0)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800">
                                                    +${{ number_format($shippingFee, 2) }}
                                                </span>
                                                @if (! empty($import?->shipping_company))
                                                    <span class="text-[10px] text-gray-400 block mt-0.5 truncate max-w-[120px]">{{ $import->shipping_company }}</span>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400 font-mono">$0.00</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-right font-bold text-gray-900 dark:text-white font-mono">
                                            ${{ number_format($lineGrandTotal, 2) }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ trans('procurement::app.demands.no-open-demands') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.demand-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateSelectionSummary();
        }

        function updateSelectionSummary() {
            const checked = document.querySelectorAll('.demand-checkbox:checked');
            const countEl = document.getElementById('selected-count');
            if (countEl) {
                countEl.innerText = checked.length;
            }

            let totalCost = 0.0;
            checked.forEach(cb => {
                const val = parseFloat(cb.getAttribute('data-grand-total') || '0');
                totalCost += val;
            });

            const totalEl = document.getElementById('selected-total-cost');
            if (totalEl) {
                totalEl.innerText = '$' + totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    </script>
</x-admin::layouts>
