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
                    {{ $order->supplier_store_name ?: ($order->supplier_store_id ?: trans('procurement::app.supplier_orders.aliexpress-store')) }} &bull; {{ trans('procurement::app.supplier_orders.batch') }}: #{{ $order->batch?->batch_number }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if ($order->state === 'ready_to_submit' && bouncer()->hasPermission('dropshipping.procurement_v2.submit'))
                    <form action="{{ route('admin.procurement.supplier_orders.submit', $order->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-blue-600 hover:bg-blue-700">
                            {{ trans('procurement::app.batches.submit-to-aliexpress') ?: 'إرسال إلى علي إكسبرس' }}
                        </button>
                    </form>
                @endif

                @if ($order->state === 'cost_variance_review' && bouncer()->hasPermission('dropshipping.procurement_v2.variance_approve'))
                    <form action="{{ route('admin.procurement.cost_variances.approve', $order->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                            {{ trans('procurement::app.cost_variances.approve') ?: 'قبول فرق التكلفة والاعتماد' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.procurement.cost_variances.reject', $order->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من رفض فرق التكلفة وإلغاء أمر الشراء؟');">
                        @csrf
                        <input type="hidden" name="reason" value="Rejected by admin operator due to high price variance">
                        <button type="submit" class="secondary-button text-rose-600 border-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950">
                            {{ trans('procurement::app.cost_variances.reject') ?: 'رفض فرق التكلفة' }}
                        </button>
                    </form>
                @endif

                @if (in_array($order->state, ['awaiting_manual_payment', 'submitted', 'payment_declared']) && bouncer()->hasPermission('dropshipping.procurement_v2.payment_confirm'))
                    <button type="button" onclick="document.getElementById('manual-payment-modal').classList.remove('hidden')" class="primary-button bg-purple-600 hover:bg-purple-700">
                        {{ trans('procurement::app.manual_payments.declare-payment') }}
                    </button>
                @endif

                @if (!in_array($order->state, ['cancelled', 'supplier_shipped', 'closed']) && bouncer()->hasPermission('dropshipping.procurement_v2.submit'))
                    <form action="{{ route('admin.procurement.supplier_orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('{{ trans('procurement::app.supplier_orders.cancel-confirm') }}');">
                        @csrf
                        <button type="submit" class="secondary-button text-rose-600 border-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950">
                            {{ trans('procurement::app.datagrid.cancel-order') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($order->state === 'cost_variance_review')
            <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">
                            مراجعة فرق التكلفة مطلوبة
                        </h3>
                        <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                            تغير سعر الصنف لدى المورد على علي إكسبرس. التكلفة المتوقعة: <strong>${{ number_format((float) $order->expected_total, 2) }}</strong> &bull; الفارق: <strong class="text-rose-600">+${{ number_format((float) $order->cost_variance_amount, 2) }}</strong> (الإجمالي الجديد: ${{ number_format((float) $order->expected_total + (float) $order->cost_variance_amount, 2) }}).
                        </p>
                    </div>
                </div>

                @if (bouncer()->hasPermission('dropshipping.procurement_v2.variance_approve'))
                    <div class="flex items-center gap-3 shrink-0">
                        <form action="{{ route('admin.procurement.cost_variances.approve', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700">
                                قبول فرق التكلفة والاعتماد
                            </button>
                        </form>

                        <form action="{{ route('admin.procurement.cost_variances.reject', $order->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رفض فرق التكلفة وإلغاء أمر الشراء؟');">
                            @csrf
                            <input type="hidden" name="reason" value="Rejected by admin operator due to high price variance">
                            <button type="submit" class="secondary-button text-rose-600 border-rose-300 hover:bg-rose-50">
                                رفض فرق التكلفة
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

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
                <table class="w-full text-sm ltr:text-left rtl:text-right text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-4 ltr:text-left rtl:text-right">{{ trans('procurement::app.supplier_orders.sku-product') }}</th>
                            <th class="p-4 text-center">{{ trans('procurement::app.supplier_orders.ordered') }}</th>
                            <th class="p-4 text-center">{{ trans('procurement::app.supplier_orders.received-good') }}</th>
                            <th class="p-4 text-center">{{ trans('procurement::app.supplier_orders.damaged') }}</th>
                            <th class="p-4 text-center">{{ trans('procurement::app.supplier_orders.missing') }}</th>
                            @if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view'))
                                <th class="p-4 ltr:text-right rtl:text-left">{{ trans('procurement::app.supplier_orders.expected-cost') }}</th>
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
                                    <td class="p-4 ltr:text-right rtl:text-left font-semibold">${{ number_format((float) $item->expected_unit_cost, 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Inbound Receipt Section --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📥</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">{{ trans('procurement::app.supplier_orders.receive-goods') }}</h2>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 rounded-full border border-emerald-200 dark:border-emerald-800">
                    المرحلة الأولى: فحص وتوريد المحطة الانتقالية
                </span>
            </div>
            <form id="receipt-goods-form" action="{{ route('admin.procurement.supplier_orders.receive', $order->id) }}" method="POST" onsubmit="return false;" class="p-5 flex flex-col gap-5">
                @csrf

                {{-- Warehouses Routing Info Banner --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 p-4 rounded-xl flex items-start gap-3">
                        <span class="text-2xl">🟢</span>
                        <div>
                            <span class="text-xs font-bold text-emerald-900 dark:text-emerald-200 block">محطة التجميع والانتقال (المخزون السليم)</span>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                                يتم قيد وتوريد كافة الكميات السليمة إلى <strong>محطة التجميع (hayest_dropship_sa)</strong> لتجهيزها للشحن للعملاء.
                            </p>
                        </div>
                    </div>

                    <div class="bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800/60 p-4 rounded-xl flex items-start gap-3">
                        <span class="text-2xl">🔴</span>
                        <div>
                            <span class="text-xs font-bold text-rose-900 dark:text-rose-200 block">مستودع الحجز الصحي (المنتجات التالفة)</span>
                            <p class="text-[11px] text-rose-700 dark:text-rose-400 mt-0.5">
                                يتم عزل وتوريد أي كميات تالفة تلقائياً إلى <strong>مستودع الحجز الصحي (hayest_quarantine_sa)</strong> للمطالبات والتعويض.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">المستودع الرئيسي للاستلام</label>
                        <select id="receipt-target-source" name="target_source" class="custom-select w-full rounded-lg border-gray-300">
                            @php
                                $whNames = [
                                    'hayest_dropship_sa'   => 'محطة التجميع والانتقال (المخزون السليم) - الرياض [hayest_dropship_sa]',
                                    'hayest_quarantine_sa' => 'مستودع الحجز الصحي والعزل (المخزون التالف) - الرياض [hayest_quarantine_sa]',
                                ];
                            @endphp
                            @foreach ($inventorySources as $src)
                                <option value="{{ $src->code }}" {{ $src->code === 'hayest_dropship_sa' ? 'selected' : '' }}>
                                    {{ $whNames[$src->code] ?? ($src->name . ' (' . $src->code . ')') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs ltr:text-left rtl:text-right text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
                        <thead class="text-gray-700 dark:text-gray-300 uppercase bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="p-3 ltr:text-left rtl:text-right">{{ trans('procurement::app.supplier_orders.sku') }}</th>
                                <th class="p-3 text-center">{{ trans('procurement::app.supplier_orders.remaining-to-receive') }}</th>
                                <th class="p-3 text-center">{{ trans('procurement::app.supplier_orders.receive-good') }}</th>
                                <th class="p-3 text-center">{{ trans('procurement::app.supplier_orders.damaged') }}</th>
                                <th class="p-3 text-center">{{ trans('procurement::app.supplier_orders.missing') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($order->items as $idx => $item)
                                @php $remaining = max(0, $item->qty_ordered - $item->qty_received_good); @endphp
                                <tr class="receipt-row" data-sku="{{ $item->supplier_sku_id }}" data-name="{{ $item->product?->name ?: $item->supplier_product_id }}">
                                    <td class="p-3 font-mono">
                                        <input type="hidden" name="lines[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $item->product?->name ?: $item->supplier_product_id }}</div>
                                        <div class="text-gray-500 font-mono text-[11px]">{{ $item->supplier_sku_id }}</div>
                                    </td>
                                    <td class="p-3 text-center font-bold text-gray-900 dark:text-white">{{ $remaining }}</td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_good]" value="{{ $remaining }}" min="0" max="{{ $remaining }}" onkeydown="if(event.key==='Enter'){event.preventDefault();return false;}" class="receipt-input-good w-20 rounded border-gray-300 text-center py-1 font-bold text-emerald-600">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_damaged]" value="0" min="0" onkeydown="if(event.key==='Enter'){event.preventDefault();return false;}" class="receipt-input-damaged w-20 rounded border-gray-300 text-center py-1 font-bold text-rose-600">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="lines[{{ $idx }}][qty_missing]" value="0" min="0" onkeydown="if(event.key==='Enter'){event.preventDefault();return false;}" class="receipt-input-missing w-20 rounded border-gray-300 text-center py-1 font-bold text-amber-600">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    <button type="button" onclick="window.openReceiptConfirmModal(event)" class="primary-button bg-emerald-600 hover:bg-emerald-700 shadow-sm">
                        {{ trans('procurement::app.supplier_orders.confirm-receipt') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Receipt Confirmation Modal --}}
    <div id="receipt-confirm-modal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800 flex flex-col gap-5">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">📥</span>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">تأكيد استلام الشحنة وتوريد المخزون</h3>
                </div>
                <button type="button" onclick="window.closeReceiptConfirmModal(event)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">✕</button>
            </div>

            <div class="flex flex-col gap-4 text-sm">
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 p-3.5 rounded-xl text-blue-900 dark:text-blue-200 text-xs space-y-1">
                    <div><strong>المستودع الرئيسي للاستلام:</strong> <span id="modal-warehouse-name" class="font-bold"></span></div>
                    <div class="text-blue-700 dark:text-blue-300">
                        • الكميات <strong>السليمة</strong> ستودع في: <strong>محطة التجميع والانتقال (hayest_dropship_sa)</strong>.<br>
                        • الكميات <strong>التالفة</strong> ستعزل في: <strong>مستودع الحجز الصحي (hayest_quarantine_sa)</strong>.<br>
                        • الكميات <strong>المفقودة</strong> ستوثق كعجز توريد للمطالبة المالية.
                    </div>
                </div>

                <div class="overflow-x-auto max-h-60 rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-xs ltr:text-left rtl:text-right text-gray-700 dark:text-gray-300">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold uppercase">
                            <tr>
                                <th class="p-2.5">الصنف</th>
                                <th class="p-2.5 text-center text-emerald-600">سليم (محطة التجميع)</th>
                                <th class="p-2.5 text-center text-rose-600">تالف (الحجز الصحي)</th>
                                <th class="p-2.5 text-center text-amber-600">مفقود (عجز)</th>
                            </tr>
                        </thead>
                        <tbody id="modal-receipt-summary-body" class="divide-y divide-gray-100 dark:divide-gray-800">
                        </tbody>
                    </table>
                </div>

                <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-3 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
                    <span class="text-base">⚠️</span>
                    <div>
                        <strong>تنبيه تأكيد نهائي:</strong> هل أنت متأكد من مطابقة هذه الكميات فعلياً على أرض الواقع؟ بعد التأكيد سيتم قيد الأصناف بالمخازن المحددة وتحديث حالة التخصيص.
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 dark:border-gray-800 pt-3">
                <button type="button" onclick="window.closeReceiptConfirmModal(event)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-semibold rounded-xl transition">
                    إلغاء وتراجع
                </button>
                <button type="button" id="modal-confirm-submit-btn" onclick="window.executeReceiptSubmission(event)" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-md transition">
                    ✓ نعم، تأكيد وحفظ الاستلام
                </button>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
    <script type="text/javascript">
        window.openReceiptConfirmModal = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const select = document.getElementById('receipt-target-source');
            const warehouseName = select ? select.options[select.selectedIndex].text : 'المستودع الانتقالي';
            const modalWh = document.getElementById('modal-warehouse-name');
            if (modalWh) modalWh.innerText = warehouseName;

            const rows = document.querySelectorAll('.receipt-row');
            const tbody = document.getElementById('modal-receipt-summary-body');
            if (tbody) tbody.innerHTML = '';

            let totalUnits = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const sku = row.getAttribute('data-sku');
                const good = parseInt(row.querySelector('.receipt-input-good')?.value || '0', 10);
                const damaged = parseInt(row.querySelector('.receipt-input-damaged')?.value || '0', 10);
                const missing = parseInt(row.querySelector('.receipt-input-missing')?.value || '0', 10);

                totalUnits += (good + damaged + missing);

                if (tbody) {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/40';
                    tr.innerHTML = `
                        <td class="p-2.5">
                            <div class="font-bold text-gray-900 dark:text-white truncate max-w-xs">${name}</div>
                            <div class="text-[10px] font-mono text-gray-500">${sku}</div>
                        </td>
                        <td class="p-2.5 text-center font-bold text-emerald-600">${good}</td>
                        <td class="p-2.5 text-center font-bold text-rose-600">${damaged}</td>
                        <td class="p-2.5 text-center font-bold text-amber-600">${missing}</td>
                    `;
                    tbody.appendChild(tr);
                }
            });

            if (totalUnits <= 0) {
                alert('الرجاء تحديد كمية استلام واحدة على الأقل (سليمة أو تالفة أو مفقودة).');
                return false;
            }

            const modal = document.getElementById('receipt-confirm-modal');
            if (modal) modal.classList.remove('hidden');
            return false;
        };

        window.closeReceiptConfirmModal = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const modal = document.getElementById('receipt-confirm-modal');
            if (modal) modal.classList.add('hidden');
            return false;
        };

        window.executeReceiptSubmission = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const form = document.getElementById('receipt-goods-form');
            if (form) {
                const btn = document.getElementById('modal-confirm-submit-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerText = 'جاري الحفظ...';
                }
                form.submit();
            }
            return false;
        };
    </script>
    @endpushOnce

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
                    <input type="text" name="external_reference" required class="custom-input w-full rounded-lg border-gray-300" placeholder="{{ trans('procurement::app.supplier_orders.external-ref-placeholder') }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">{{ trans('procurement::app.manual_payments.declared-amount-usd') }}</label>
                    <input type="number" step="0.01" name="declared_total" value="{{ $order->expected_total }}" required class="custom-input w-full rounded-lg border-gray-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">{{ trans('procurement::app.general.notes') }}</label>
                    <textarea name="notes" rows="2" class="custom-textarea w-full rounded-lg border-gray-300" placeholder="{{ trans('procurement::app.supplier_orders.notes-placeholder') }}"></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" onclick="document.getElementById('manual-payment-modal').classList.add('hidden')" class="secondary-button">{{ trans('procurement::app.general.cancel') }}</button>
                    <button type="submit" class="primary-button bg-purple-600 hover:bg-purple-700">{{ trans('procurement::app.manual_payments.confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
