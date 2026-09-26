<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.receipts.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6 w-full max-w-6xl">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.receipts.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.receipts.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.receipts.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-2">
                    <span class="icon-done text-2xl text-emerald-600"></span>
                    {{ trans('inventory::app.admin.receipts.create-title') }}
                </h1>
            </div>

            <a href="{{ route('admin.inventory.receipts.index') }}" class="secondary-button">
                ← إلغاء وعودة
            </a>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.inventory.receipts.store') }}" class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-6" id="receipt-form">
            @csrf

            {{-- Link to Transfer Manifest --}}
            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300">
                            مانيفست النقل المرتبط (استدعاء عناصر الطرد تلقائياً)
                        </label>
                        <button type="button" onclick="window.refreshManifestDetails()" class="text-xs text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-1 font-semibold">
                            <span>🔄</span>
                            <span>استدعاء البنود</span>
                        </button>
                    </div>
                    <select name="inventory_transfer_manifest_id" 
                            id="manifest-select" 
                            onchange="window.handleManifestSelectChange ? window.handleManifestSelectChange(this.value) : null"
                            class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold text-blue-700 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- اختر مانيفست النقل لاستدعاء محتويات طرده تلقائياً --</option>
                        @foreach($activeTransfers as $trf)
                            @php
                                $trfStatus = $trf->status instanceof \BackedEnum ? $trf->status->value : $trf->status;
                            @endphp
                            <option value="{{ $trf->id }}" 
                                    data-carrier="{{ $trf->carrier_name }}" 
                                    data-tracking="{{ $trf->tracking_number }}" 
                                    data-dest="{{ $trf->destination_inventory_source_id }}"
                                    {{ ($transferManifest && $transferManifest->id == $trf->id) ? 'selected' : '' }}>
                                {{ $trf->manifest_number }} ({{ $trfStatus }}) - {{ $trf->carrier_name ?: 'بدون ناقل' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-gray-500">عند اختيار مانيفست، يتم استدعاء جميع بنود وعناصر الطرد المشحونة ومستودع الوجهة فوراً.</span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        مرجع خارجي / رقم الشحنة
                    </label>
                    <input type="text" name="external_reference" id="external-reference-input" value="{{ old('external_reference', $transferManifest?->tracking_number ?: $transferManifest?->manifest_number) }}" placeholder="رقم الشحنة أو بوليصة الناقل" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                </div>
            </div>

            {{-- Destination and Quarantine Selectors --}}
            <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                        {{ trans('inventory::app.admin.receipts.destination') }}
                    </label>
                    <select name="destination_inventory_source_id" id="destination-source-select" required class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold text-emerald-700 dark:text-emerald-400 focus:ring-2 focus:ring-emerald-500">
                        @foreach($destinationSources as $src)
                            <option value="{{ $src->id }}" {{ ($transferManifest && $transferManifest->destination_inventory_source_id == $src->id) ? 'selected' : '' }}>
                                {{ $src->name }} ({{ $src->code }})
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">✓ المستودع المادي اليمني الذي ستدخل فيه البضاعة السليمة المقبولة.</span>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.receipts.quarantine-destination') }}
                    </label>
                    <select name="quarantine_inventory_source_id" id="quarantine-source-select" class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold text-rose-700 dark:text-rose-400 focus:ring-2 focus:ring-rose-500">
                        @foreach($quarantineSources as $qsrc)
                            <option value="{{ $qsrc->id }}" {{ $qsrc->code === 'hayest_quarantine_ye' ? 'selected' : '' }}>
                                {{ $qsrc->name }} ({{ $qsrc->code }})
                            </option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">⚠️ المستودع المحجور الذي ستوجه إليه البضائع التالفة أو المعيبة تلقائياً.</span>
                </div>
            </div>

            {{-- Loading State Indicator --}}
            <div id="manifest-loading" class="hidden p-4 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 flex items-center justify-center gap-2 text-xs text-blue-700 dark:text-blue-300">
                <svg class="animate-spin h-5 w-5 text-blue-600" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span class="font-bold">جاري استدعاء بنود ومحتويات الطرد من المانيفست...</span>
            </div>

            {{-- Items Grid Section --}}
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between flex-wrap gap-2 border-b pb-2 border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="icon-done text-lg text-emerald-600"></span>
                        فحص وتصنيف بنود الطرد المستلمة (سليم / تالف / عجز ونقص)
                    </h3>
                    <span id="items-badge-count" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ ($transferManifest && $transferManifest->items) ? count($transferManifest->items) : 1 }} بنود
                    </span>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <table class="w-full text-xs text-right border-collapse" id="receipt-items-table">
                        <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-3 w-10 text-center">#</th>
                                <th class="p-3 w-56 md:w-64 max-w-[260px]">تفاصيل الصنف / SKU</th>
                                <th class="p-3 w-28 text-center">الكمية المشحونة</th>
                                <th class="p-3 w-36 text-center text-emerald-700 dark:text-emerald-400">السليم (Good) ✓</th>
                                <th class="p-3 w-36 text-center text-rose-700 dark:text-rose-400">التالف (Damaged) ✕</th>
                                <th class="p-3 w-36 text-center text-amber-700 dark:text-amber-400">الناقص (Missing) ⚠️</th>
                                <th class="p-3 w-28 text-center">إجراء سريع</th>
                            </tr>
                        </thead>
                        <tbody id="receipt-items-body" class="divide-y divide-gray-100 dark:divide-gray-800">
                            @if($transferManifest && $transferManifest->items->isNotEmpty())
                                @foreach($transferManifest->items as $index => $item)
                                    @php
                                        $productName = $item->product?->name ?: $item->sku;
                                    @endphp
                                    <tr class="item-row hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors" data-row-index="{{ $index }}">
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                        <input type="hidden" name="items[{{ $index }}][sku]" value="{{ $item->sku }}">
                                        <input type="hidden" name="items[{{ $index }}][inventory_transfer_manifest_item_id]" value="{{ $item->id }}">
                                        
                                        <td class="p-3 text-center text-gray-400 font-mono">{{ $index + 1 }}</td>
                                        <td class="p-3 w-56 md:w-64 max-w-[260px]">
                                            <div class="flex items-center gap-2.5">
                                                @if ($item->product?->base_image_url)
                                                    <img src="{{ $item->product->base_image_url }}" class="w-8 h-8 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0" alt="">
                                                @else
                                                    <div class="w-8 h-8 rounded-md bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 font-bold text-xs shrink-0"><span class="icon-product"></span></div>
                                                @endif
                                                <div class="flex flex-col min-w-0">
                                                    <span class="font-bold text-xs text-gray-900 dark:text-white leading-snug whitespace-normal break-words line-clamp-3 hover:line-clamp-none transition-all cursor-pointer" title="{{ $productName }}">{{ $productName }}</span>
                                                    <span class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: {{ $item->sku }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center font-bold text-blue-600 font-mono text-sm">
                                            <span class="shipped-qty-val">{{ $item->qty_shipped }}</span> قطعة
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'good', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                                <input type="number" 
                                                       name="items[{{ $index }}][qty_good]" 
                                                       id="qty-good-{{ $index }}"
                                                       value="{{ $item->qty_shipped }}" 
                                                       min="0" 
                                                       max="{{ $item->qty_shipped }}"
                                                       class="qty-good-input w-16 px-1.5 py-1 text-center font-bold rounded border border-emerald-300 dark:border-emerald-800 bg-emerald-50/30 text-emerald-700 text-xs">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'good', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'damaged', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                                <input type="number" 
                                                       name="items[{{ $index }}][qty_damaged]" 
                                                       id="qty-damaged-{{ $index }}"
                                                       value="0" 
                                                       min="0" 
                                                       max="{{ $item->qty_shipped }}"
                                                       class="qty-damaged-input w-16 px-1.5 py-1 text-center font-bold rounded border border-rose-300 dark:border-rose-800 bg-rose-50/30 text-rose-700 text-xs">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'damaged', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'missing', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                                <input type="number" 
                                                       name="items[{{ $index }}][qty_missing]" 
                                                       id="qty-missing-{{ $index }}"
                                                       value="0" 
                                                       min="0" 
                                                       max="{{ $item->qty_shipped }}"
                                                       class="qty-missing-input w-16 px-1.5 py-1 text-center font-bold rounded border border-amber-300 dark:border-amber-800 bg-amber-50/30 text-amber-700 text-xs">
                                                <button type="button" onclick="window.stepRowQty({{ $index }}, 'missing', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" onclick="window.setRowAllGood({{ $index }})" class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 hover:bg-emerald-200 text-emerald-800" title="تعيين كامل الكمية سليمة">سليم</button>
                                                <button type="button" onclick="window.setRowAllDamaged({{ $index }})" class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 hover:bg-rose-200 text-rose-800" title="تعيين كامل الكمية تالفة">تالف</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="item-row" data-row-index="0">
                                    <td colspan="7" class="p-8 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="icon-package text-3xl text-gray-300 dark:text-gray-600"></span>
                                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">اختر مانيفست النقل من القائمة بالأعلى</span>
                                            <span class="text-[11px] text-gray-500">سيتم استدعاء عناصر الطرد وتفاصيل المنتجات والكميات المشحونة تلقائياً هنا</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 font-bold text-xs">
                            <tr>
                                <td colspan="2" class="p-3 text-gray-700 dark:text-gray-300 font-bold">
                                    إجمالي الفحص الميداني:
                                </td>
                                <td class="p-3 text-center text-blue-600 font-mono text-sm" id="foot-total-shipped">
                                    {{ ($transferManifest && $transferManifest->items) ? $transferManifest->items->sum('qty_shipped') : 0 }}
                                </td>
                                <td class="p-3 text-center text-emerald-600 font-mono text-sm" id="foot-total-good">
                                    {{ ($transferManifest && $transferManifest->items) ? $transferManifest->items->sum('qty_shipped') : 0 }}
                                </td>
                                <td class="p-3 text-center text-rose-600 font-mono text-sm" id="foot-total-damaged">
                                    0
                                </td>
                                <td class="p-3 text-center text-amber-600 font-mono text-sm" id="foot-total-missing">
                                    0
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Inventory Routing Impact Live Card --}}
                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1 pt-2">
                    <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-emerald-900 dark:text-emerald-200 font-medium">الكمية السليمة المقبولة:</span>
                        </div>
                        <span id="summary-good-route" class="font-bold text-emerald-800 dark:text-emerald-300">0 قطعة -> ستدخل المستودع المستلم</span>
                    </div>

                    <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="text-rose-900 dark:text-rose-200 font-medium">الكمية التالفة المعيبة:</span>
                        </div>
                        <span id="summary-damaged-route" class="font-bold text-rose-800 dark:text-rose-300">0 قطعة -> ستوجه لمستودع الحجر</span>
                    </div>
                </div>

                {{-- Discrepancy Status Warning Alert --}}
                <div id="discrepancy-alert" class="hidden p-3 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 flex items-center justify-between text-xs text-amber-800 dark:text-amber-200">
                    <div class="flex items-center gap-2">
                        <span class="icon-warning text-base text-amber-600"></span>
                        <span id="discrepancy-message">تم رصد كميات تالفة أو ناقصة. سيتم تحويل التوالف للحجر وتوثيق العجز كفرق مانيفست.</span>
                    </div>
                </div>
            </div>

            {{-- Notes --}}
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    {{ trans('inventory::app.admin.receipts.inspection-notes') }}
                </label>
                <textarea name="notes" rows="2" placeholder="ملاحظات فحص وتدقيق الجودة، حالة الصناديق والأغلفة، أو أسباب العجز والتلف..." class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
                <button type="submit" class="primary-button bg-emerald-600 hover:bg-emerald-700 text-sm py-2.5 px-6 font-bold shadow-sm flex items-center gap-2">
                    <span class="icon-done text-lg"></span>
                    <span>{{ trans('inventory::app.admin.receipts.confirm-receipt') }}</span>
                </button>
            </div>
        </form>
    </div>

    @pushOnce('scripts')
        <script>
            (function () {
                const getEl = (id) => document.getElementById(id);
                window.currentManifestItems = [];

                // Handle Manifest Selection Change
                window.handleManifestSelectChange = function (manifestId) {
                    if (!manifestId) {
                        const sel = getEl('manifest-select');
                        manifestId = sel ? sel.value : null;
                    }

                    if (!manifestId) {
                        return;
                    }

                    console.log('[InboundReceipt] Fetching manifest details for ID:', manifestId);
                    const loading = getEl('manifest-loading');
                    if (loading) loading.classList.remove('hidden');

                    const baseUrl = "{{ url(config('app.admin_url', 'admin') . '/inventory/receipts/manifest-details') }}";
                    const url = baseUrl + '/' + manifestId;

                    fetch(url, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        if (loading) loading.classList.add('hidden');
                        if (data && data.items) {
                            window.currentManifestItems = data.items;
                            window.renderManifestItems(data);
                        }
                    })
                    .catch(err => {
                        if (loading) loading.classList.add('hidden');
                        console.error('[InboundReceipt] Error fetching manifest details:', err);
                        alert('حدث خطأ أثناء جلب تفاصيل المانيفست (' + err.message + ').');
                    });
                };

                window.refreshManifestDetails = function () {
                    const sel = getEl('manifest-select');
                    if (sel && sel.value) {
                        window.handleManifestSelectChange(sel.value);
                    } else {
                        alert('يرجى اختيار المانيفست أولاً.');
                    }
                };

                // Render manifest items into table
                window.renderManifestItems = function (data) {
                    const destSelect = getEl('destination-source-select');
                    const extRef = getEl('external-reference-input');
                    const itemsBody = getEl('receipt-items-body');
                    const badge = getEl('items-badge-count');

                    // Auto-set destination warehouse
                    if (destSelect && data.destination_inventory_source_id) {
                        destSelect.value = data.destination_inventory_source_id;
                    }

                    // Auto-set external reference
                    if (extRef) {
                        if (data.tracking_number) {
                            extRef.value = data.tracking_number;
                        } else if (data.manifest_number) {
                            extRef.value = data.manifest_number;
                        }
                    }

                    if (!itemsBody) return;
                    itemsBody.innerHTML = '';

                    if (!data.items || data.items.length === 0) {
                        itemsBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="p-6 text-center text-gray-400 text-xs">
                                    لا توجد بنود مشحونة في هذا المانيفست.
                                </td>
                            </tr>
                        `;
                        if (badge) badge.textContent = '0 بنود';
                        window.recalculateTotals();
                        return;
                    }

                    if (badge) badge.textContent = `${data.items.length} بنود`;

                    data.items.forEach((item, index) => {
                        const tr = document.createElement('tr');
                        tr.className = 'item-row hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors';
                        tr.dataset.rowIndex = index;

                        const imgHtml = item.image_url
                            ? `<img src="${item.image_url}" class="w-9 h-9 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0" alt="">`
                            : `<div class="w-9 h-9 rounded-md bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 font-bold text-xs shrink-0"><span class="icon-product"></span></div>`;

                        tr.innerHTML = `
                            <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                            <input type="hidden" name="items[${index}][sku]" value="${item.sku}">
                            <input type="hidden" name="items[${index}][inventory_transfer_manifest_item_id]" value="${item.id}">

                            <td class="p-3 text-center text-gray-400 font-mono">${index + 1}</td>
                            <td class="p-3 w-56 md:w-64 max-w-[260px]">
                                <div class="flex items-center gap-2.5">
                                    ${imgHtml}
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-xs text-gray-900 dark:text-white leading-snug whitespace-normal break-words line-clamp-3 hover:line-clamp-none transition-all cursor-pointer" title="${item.name}">${item.name}</span>
                                        <span class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: ${item.sku}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-center font-bold text-blue-600 font-mono text-sm">
                                <span class="shipped-qty-val" id="shipped-val-${index}">${item.qty_shipped}</span> قطعة
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'good', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                    <input type="number" 
                                           name="items[${index}][qty_good]" 
                                           id="qty-good-${index}"
                                           value="${item.qty_good}" 
                                           min="0" 
                                           max="${item.qty_shipped}"
                                           onchange="window.recalculateTotals()"
                                           class="qty-good-input w-16 px-1.5 py-1 text-center font-bold rounded border border-emerald-300 dark:border-emerald-800 bg-emerald-50/30 text-emerald-700 text-xs">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'good', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'damaged', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                    <input type="number" 
                                           name="items[${index}][qty_damaged]" 
                                           id="qty-damaged-${index}"
                                           value="${item.qty_damaged}" 
                                           min="0" 
                                           max="${item.qty_shipped}"
                                           onchange="window.recalculateTotals()"
                                           class="qty-damaged-input w-16 px-1.5 py-1 text-center font-bold rounded border border-rose-300 dark:border-rose-800 bg-rose-50/30 text-rose-700 text-xs">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'damaged', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'missing', -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">-</button>
                                    <input type="number" 
                                           name="items[${index}][qty_missing]" 
                                           id="qty-missing-${index}"
                                           value="${item.qty_missing}" 
                                           min="0" 
                                           max="${item.qty_shipped}"
                                           onchange="window.recalculateTotals()"
                                           class="qty-missing-input w-16 px-1.5 py-1 text-center font-bold rounded border border-amber-300 dark:border-amber-800 bg-amber-50/30 text-amber-700 text-xs">
                                    <button type="button" onclick="window.stepRowQty(${index}, 'missing', 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 font-bold">+</button>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.setRowAllGood(${index})" class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 hover:bg-emerald-200 text-emerald-800" title="تعيين كامل الكمية سليمة">سليم</button>
                                    <button type="button" onclick="window.setRowAllDamaged(${index})" class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 hover:bg-rose-200 text-rose-800" title="تعيين كامل الكمية تالفة">تالف</button>
                                </div>
                            </td>
                        `;

                        itemsBody.appendChild(tr);
                    });

                    window.recalculateTotals();
                };

                // Quick buttons: All Good / All Damaged
                window.setRowAllGood = function (index) {
                    const shippedSpan = getEl(`shipped-val-${index}`);
                    const shipped = shippedSpan ? parseInt(shippedSpan.textContent) || 0 : 0;

                    const goodInput = getEl(`qty-good-${index}`);
                    const damagedInput = getEl(`qty-damaged-${index}`);
                    const missingInput = getEl(`qty-missing-${index}`);

                    if (goodInput) goodInput.value = shipped;
                    if (damagedInput) damagedInput.value = 0;
                    if (missingInput) missingInput.value = 0;

                    window.recalculateTotals();
                };

                window.setRowAllDamaged = function (index) {
                    const shippedSpan = getEl(`shipped-val-${index}`);
                    const shipped = shippedSpan ? parseInt(shippedSpan.textContent) || 0 : 0;

                    const goodInput = getEl(`qty-good-${index}`);
                    const damagedInput = getEl(`qty-damaged-${index}`);
                    const missingInput = getEl(`qty-missing-${index}`);

                    if (goodInput) goodInput.value = 0;
                    if (damagedInput) damagedInput.value = shipped;
                    if (missingInput) missingInput.value = 0;

                    window.recalculateTotals();
                };

                // Stepper for Good, Damaged, Missing
                window.stepRowQty = function (index, type, delta) {
                    const input = getEl(`qty-${type}-${index}`);
                    if (!input) return;

                    let val = (parseInt(input.value) || 0) + delta;
                    if (val < 0) val = 0;
                    input.value = val;

                    window.recalculateTotals();
                };

                // Recalculate totals and live routing summary
                window.recalculateTotals = function () {
                    let totalShipped = 0;
                    let totalGood = 0;
                    let totalDamaged = 0;
                    let totalMissing = 0;

                    document.querySelectorAll('.item-row').forEach(row => {
                        const shippedSpan = row.querySelector('.shipped-qty-val');
                        const shipped = shippedSpan ? parseInt(shippedSpan.textContent) || 0 : 0;
                        const good = parseInt(row.querySelector('.qty-good-input')?.value) || 0;
                        const damaged = parseInt(row.querySelector('.qty-damaged-input')?.value) || 0;
                        const missing = parseInt(row.querySelector('.qty-missing-input')?.value) || 0;

                        totalShipped += shipped;
                        totalGood += good;
                        totalDamaged += damaged;
                        totalMissing += missing;
                    });

                    const footShipped = getEl('foot-total-shipped');
                    const footGood = getEl('foot-total-good');
                    const footDamaged = getEl('foot-total-damaged');
                    const footMissing = getEl('foot-total-missing');

                    if (footShipped) footShipped.textContent = totalShipped;
                    if (footGood) footGood.textContent = totalGood;
                    if (footDamaged) footDamaged.textContent = totalDamaged;
                    if (footMissing) footMissing.textContent = totalMissing;

                    // Update live routing impact labels
                    const destSelect = getEl('destination-source-select');
                    const destName = destSelect && destSelect.options[destSelect.selectedIndex] ? destSelect.options[destSelect.selectedIndex].text : 'المستودع المستلم';
                    
                    const qSelect = getEl('quarantine-source-select');
                    const qName = qSelect && qSelect.options[qSelect.selectedIndex] ? qSelect.options[qSelect.selectedIndex].text : 'مستودع الحجر';

                    const summaryGood = getEl('summary-good-route');
                    const summaryDamaged = getEl('summary-damaged-route');

                    if (summaryGood) summaryGood.textContent = `${totalGood} قطعة -> ستدخل رصيد (${destName})`;
                    if (summaryDamaged) summaryDamaged.textContent = `${totalDamaged} قطعة -> ستوجه لحجر (${qName})`;

                    // Discrepancy warning
                    const discrepancyAlert = getEl('discrepancy-alert');
                    if (discrepancyAlert) {
                        if (totalDamaged > 0 || totalMissing > 0) {
                            discrepancyAlert.classList.remove('hidden');
                        } else {
                            discrepancyAlert.classList.add('hidden');
                        }
                    }
                };

                // Fallback delegated change listener on document
                document.addEventListener('change', function (e) {
                    if (e.target && e.target.id === 'manifest-select') {
                        window.handleManifestSelectChange(e.target.value);
                    }
                    if (e.target && (e.target.id === 'destination-source-select' || e.target.id === 'quarantine-source-select')) {
                        window.recalculateTotals();
                    }
                });

                // Auto initialize on load if manifest is already selected
                function autoInit() {
                    const sel = getEl('manifest-select');
                    if (sel && sel.value) {
                        console.log('[InboundReceipt] Auto-loading manifest on startup:', sel.value);
                        window.handleManifestSelectChange(sel.value);
                    }
                }

                if (document.readyState === 'complete') {
                    autoInit();
                } else {
                    window.addEventListener('load', autoInit);
                }
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
