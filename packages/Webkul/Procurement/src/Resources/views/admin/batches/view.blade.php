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
                @if (in_array($batch->state, ['ready_for_review', 'exception']) && bouncer()->hasPermission('dropshipping.procurement_v2.batch_approve'))
                    <form action="{{ route('admin.procurement.batches.approve', $batch->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                            {{ trans('procurement::app.batches.approve') }}
                        </button>
                    </form>
                @endif

                @if (in_array($batch->state, ['approved', 'partially_submitted', 'exception', 'ready_for_review']) && bouncer()->hasPermission('dropshipping.procurement_v2.submit'))
                    <form action="{{ route('admin.procurement.batches.submit', $batch->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-blue-600 hover:bg-blue-700">
                            {{ trans('procurement::app.batches.submit-to-aliexpress') }}
                        </button>
                    </form>
                @endif

                @if (in_array($batch->state, ['ready_for_review', 'approved', 'exception']) && bouncer()->hasPermission('dropshipping.procurement_v2.batch_approve'))
                    <form action="{{ route('admin.procurement.batches.reject', $batch->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إلغاء الدفعة وإعادة كافة الطلبات إلى مرحلة احتياجات الشراء والتجميع؟')">
                        @csrf
                        <input type="hidden" name="reason" value="إلغاء وإعادة لمرحلة التجميع بواسطة المشرف">
                        <button type="submit" class="secondary-button text-rose-600 border-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center gap-1.5 font-semibold">
                            <span>↩️</span>
                            <span>إعادة كامل الدفعة إلى مرحلة التجميع</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $hasVarianceSpo = $batch->supplierOrders->contains('state', 'cost_variance_review');
        @endphp

        @if ($batch->state === 'exception')
            @php
                $lastHaltLog = \Webkul\Procurement\Models\ProcurementAuditLog::where('auditable_type', \Webkul\Procurement\Models\ProcurementBatch::class)
                    ->where('auditable_id', $batch->id)
                    ->whereIn('action', ['batch_preflight_halted', 'batch_submission_failed'])
                    ->latest('id')
                    ->first();
                $reasons = $lastHaltLog?->details['reasons'] ?? [];
            @endphp
            <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-700 rounded-xl p-5 shadow-sm flex flex-col gap-3">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🛑</span>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-rose-900 dark:text-rose-200">
                            تعذر إرسال الدفعة لوجود تعثر في الفحص المسبق (تم إيقاف الإرسال بالكامل لحماية الطلب)
                        </h3>
                        @if (! empty($reasons))
                            <ul class="text-sm text-rose-700 dark:text-rose-300 mt-2 list-disc list-inside space-y-1 font-medium">
                                @foreach ($reasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <p class="text-xs text-rose-600 dark:text-rose-400 mt-2">
                            💡 يمكنك النقر على زر <strong>(إزالة من الدفعة)</strong> بجانب أمر المورد المتعثر أدناه لإعادته إلى احتياجات الشراء، ثم إرسال باقي الدفعة السليمة فوراً.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Batch Information Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase">{{ trans('procurement::app.datagrid.state') }}</span>
                <div class="mt-2">
                    @php
                        $badgeClasses = match($batch->state) {
                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'partially_submitted' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700',
                            'awaiting_manual_payment' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                            'cost_variance_review' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                            'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                            'exception' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold {{ $badgeClasses }}">
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ in_array($spo->state, ['submitted', 'ready_to_submit']) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : ($spo->state === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300') }}">
                                    {{ trans("procurement::app.states.{$spo->state}") ?: $spo->state }}
                                </span>
                                @php
                                    $hasLiveAliExpressOrder = $spo->platformOrders()
                                        ->whereNotNull('external_order_id')
                                        ->where('external_order_id', '!=', '')
                                        ->where('normalized_status', '!=', \Webkul\Procurement\Models\ExternalPlatformOrder::STATUS_SUBMISSION_FAILED)
                                        ->exists();
                                @endphp

                                @if (in_array($spo->state, ['draft', 'ready_to_submit', 'supplier_exception', 'cost_variance_review']) && in_array($batch->state, ['approved', 'partially_submitted', 'exception']) && bouncer()->hasPermission('dropshipping.procurement_v2.submit'))
                                    <form action="{{ route('admin.procurement.supplier_orders.submit', $spo->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="primary-button text-xs py-1 px-3 bg-blue-600 hover:bg-blue-700">
                                            {{ trans('procurement::app.batches.submit-to-aliexpress') }}
                                        </button>
                                    </form>
                                @endif

                                @if (! $hasLiveAliExpressOrder && in_array($batch->state, ['ready_for_review', 'approved', 'partially_submitted', 'exception']) && bouncer()->hasPermission('dropshipping.procurement_v2.batch_approve'))
                                    <form action="{{ route('admin.procurement.batches.remove_supplier_order', ['batch' => $batch->id, 'spo' => $spo->id]) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من إزالة أمر هذا المورد وإعادة طلباته إلى مرحلة التجميع (احتياجات الشراء)؟')">
                                        @csrf
                                        <button type="submit" class="text-xs text-rose-700 dark:text-rose-300 hover:text-rose-900 dark:hover:text-rose-100 font-semibold border border-rose-300 dark:border-rose-800 bg-rose-50/70 hover:bg-rose-100 dark:bg-rose-950/40 px-2.5 py-1 rounded-lg transition-all flex items-center gap-1 shadow-sm" title="إزالة هذا المورد وفك ارتباط منتجاته وإعادتها فوراً إلى شاشة احتياجات الشراء">
                                            <span>↩️</span>
                                            <span>إزالة وإعادة لاحتياجات الشراء</span>
                                        </button>
                                    </form>
                                @endif
                                @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                    <div class="flex items-center gap-2">
                                        @if ((float) $spo->expected_shipping_total > 0)
                                            <span class="text-xs text-amber-700 bg-amber-50 dark:bg-amber-950/30 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800 font-mono">
                                                شحن: +${{ number_format((float) $spo->expected_shipping_total, 2) }}
                                            </span>
                                        @endif
                                        <span class="font-bold text-gray-900 dark:text-white text-sm font-mono">
                                            ${{ number_format((float) $spo->expected_total, 2) }}
                                        </span>
                                    </div>
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
                                            <th class="p-2.5 ltr:text-right rtl:text-left">{{ trans('procurement::app.datagrid.shipping-fee') }}</th>
                                            <th class="p-2.5 ltr:text-right rtl:text-left">{{ trans('procurement::app.datagrid.total-cost-with-shipping') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($spo->items as $item)
                                        @php
                                            $import = \App\Models\AliExpressProductImport::where('aliexpress_product_id', $item->supplier_product_id)
                                                ->orWhere('product_id', $item->product_id)
                                                ->latest('id')
                                                ->first();

                                            $isChoice = $import && (
                                                $import->is_choice 
                                                || stripos((string) $import->shipping_company, 'choice') !== false 
                                                || (float) $import->base_shipping_cost == 0
                                            );
                                            $shippingFee = ($isChoice || ! $import) ? 0.0 : (float) ($import->base_shipping_cost ?? 0.0);
                                            $itemCost = (float) ($item->qty_ordered * $item->expected_unit_cost);
                                            $itemGrandTotal = $itemCost + $shippingFee;
                                        @endphp
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
                                                <td class="p-2.5 ltr:text-right rtl:text-left font-mono">${{ number_format((float) $item->expected_unit_cost, 2) }}</td>
                                                <td class="p-2.5 ltr:text-right rtl:text-left">
                                                    @if ($isChoice)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800">
                                                            Choice
                                                        </span>
                                                        <span class="text-xs text-gray-500 font-mono ml-1">$0.00</span>
                                                    @elseif ($shippingFee > 0)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800 font-mono">
                                                            +${{ number_format($shippingFee, 2) }}
                                                        </span>
                                                        @if (! empty($import?->shipping_company))
                                                            <span class="text-[10px] text-gray-400 block mt-0.5 truncate max-w-[120px]">{{ $import->shipping_company }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-xs text-gray-400 font-mono">$0.00</span>
                                                    @endif
                                                </td>
                                                <td class="p-2.5 ltr:text-right rtl:text-left font-bold text-gray-900 dark:text-white font-mono">
                                                    ${{ number_format($itemGrandTotal, 2) }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view') && $spo->items->count() > 1)
                                    <tfoot class="bg-gray-100/70 dark:bg-gray-800/60 border-t border-gray-200 dark:border-gray-700 font-semibold text-gray-800 dark:text-gray-200">
                                        <tr>
                                            <td colspan="3" class="p-2.5 text-right font-bold">الإجمالي:</td>
                                            <td class="p-2.5 ltr:text-right rtl:text-left font-mono">${{ number_format((float) ($spo->expected_items_total ?: $spo->items->sum(fn($i) => $i->qty_ordered * $i->expected_unit_cost)), 2) }}</td>
                                            <td class="p-2.5 ltr:text-right rtl:text-left">
                                                @if ((float) $spo->expected_shipping_total > 0)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-800 font-mono">
                                                        +${{ number_format((float) $spo->expected_shipping_total, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500 font-mono">$0.00</span>
                                                @endif
                                            </td>
                                            <td class="p-2.5 ltr:text-right rtl:text-left font-bold text-sm text-emerald-600 dark:text-emerald-400 font-mono">
                                                ${{ number_format((float) $spo->expected_total, 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin::layouts>
