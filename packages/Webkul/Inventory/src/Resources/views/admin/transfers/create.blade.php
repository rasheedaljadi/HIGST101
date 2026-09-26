<x-admin::layouts>
    <x-slot:title>
        {{ trans('inventory::app.admin.transfers.create-title') }}
    </x-slot>

    <div class="flex flex-col gap-6 w-full">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.inventory.transfers.index') }}" class="hover:underline">
                        {{ trans('inventory::app.admin.transfers.title') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">{{ trans('inventory::app.admin.transfers.create-title') }}</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-2">
                    <span class="icon-package text-2xl text-blue-600"></span>
                    {{ trans('inventory::app.admin.transfers.create-title') }}
                </h1>
            </div>

            <a href="{{ route('admin.inventory.transfers.index') }}" class="secondary-button">
                ← إلغاء وعودة
            </a>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('admin.inventory.transfers.store') }}" id="transfer-create-form" class="flex flex-col gap-6">
            @csrf

            {{-- 1. Route & Logistics Header Card --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                <div class="flex items-center justify-between border-b pb-3 border-gray-100 dark:border-gray-800">
                    <h2 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="icon-truck text-base text-blue-600"></span>
                        بيانات المسار اللوجستي والشحن
                    </h2>
                    <span class="text-xs text-gray-500">الخطوة 1: حدد المستودع المشحن والمستلم</span>
                </div>

                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    {{-- Origin Source --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                            {{ trans('inventory::app.admin.transfers.source') }}
                        </label>
                        <select name="source_inventory_source_id" 
                                id="source-warehouse-select" 
                                onchange="window.handleSourceWarehouseChange ? window.handleSourceWarehouseChange(this.value) : null"
                                required 
                                class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold text-blue-700 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                            <option value="">-- اختر المستودع المشحن لعرض بضائعه تلقائياً --</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}" {{ old('source_inventory_source_id') == $source->id ? 'selected' : '' }}>
                                    {{ $source->name }} ({{ $source->code }}) - {{ $source->country }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-gray-500">عند اختيار المستودع، ستظهر كافة بضائعه المتوفرة فوراً في الجدول الجانبي.</span>
                    </div>

                    {{-- Destination Source --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                            {{ trans('inventory::app.admin.transfers.destination') }}
                        </label>
                        <select name="destination_inventory_source_id" id="destination-warehouse-select" required class="px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-semibold focus:ring-2 focus:ring-blue-500">
                            <option value="">-- اختر المستودع الهدف المستلم --</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}" {{ old('destination_inventory_source_id') == $source->id ? 'selected' : '' }}>
                                    {{ $source->name }} ({{ $source->code }}) - {{ $source->country }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 max-sm:grid-cols-1 pt-2">
                    {{-- Carrier --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ trans('inventory::app.admin.transfers.carrier') }}
                        </label>
                        <input type="text" name="carrier_name" value="{{ old('carrier_name') }}" placeholder="مثال: HIGHEST Express / DHL" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                    </div>

                    {{-- Tracking Number --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ trans('inventory::app.admin.transfers.tracking-number') }}
                        </label>
                        <input type="text" name="tracking_number" value="{{ old('tracking_number') }}" placeholder="رقم بوليصة الشحن والتتبع" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                    </div>

                    {{-- Total Packages --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                            {{ trans('inventory::app.admin.transfers.packages-count') }}
                        </label>
                        <input type="number" name="total_packages" value="{{ old('total_packages', 1) }}" min="1" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm">
                    </div>
                </div>
            </div>

            {{-- 2. Side-by-Side Dual Table Workspace --}}
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
                
                {{-- RIGHT PANEL: Available Stock in Selected Origin Warehouse --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center justify-between flex-wrap gap-2 border-b pb-3 border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                                بضائع المخزن المتاحة للشحن
                            </h3>
                            <span id="available-count-badge" class="px-2 py-0.5 text-xs rounded-full font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 hidden">
                                0 صنف
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" onclick="window.refreshSourceProducts()" class="secondary-button text-xs py-1 px-2.5 flex items-center gap-1 hover:bg-blue-50 hover:text-blue-700 border-blue-200" title="إعادة تحميل بضائع المخزن">
                                <span>🔄</span>
                                <span>تحديث</span>
                            </button>
                            <button type="button" id="add-all-btn" onclick="window.addAllStockToParcel()" class="secondary-button text-xs py-1 px-2.5 hidden hover:bg-emerald-50 hover:text-emerald-700 border-emerald-200 font-bold" title="إضافة جميع الأصناف المعروضة">
                                + إضافة كل البضائع
                            </button>
                        </div>
                    </div>

                    {{-- Instant Local Search Filter --}}
                    <div class="relative">
                        <input type="text" 
                               id="stock-filter-input" 
                               oninput="window.filterStockProducts ? window.filterStockProducts(this.value) : null"
                               placeholder="تصفية سريعة بالاسم أو SKU..." 
                               autocomplete="off" 
                               class="w-full px-3 py-2 pl-8 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs focus:bg-white dark:focus:bg-gray-900 focus:border-blue-500">
                        <div class="absolute left-2.5 top-2.5 text-gray-400">
                            <span class="icon-search text-sm"></span>
                        </div>
                    </div>

                    {{-- Available Stock Table Container --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-[540px] overflow-y-auto">
                        <table class="w-full text-xs text-right border-collapse">
                            <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-2.5 w-44 md:w-52 max-w-[220px]">المنتج (الاسم و SKU)</th>
                                    <th class="p-2.5 w-24 text-center">المتوفر</th>
                                    <th class="p-2.5 w-32 text-center">الكمية</th>
                                    <th class="p-2.5 w-28 text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody id="stock-table-body" class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr id="stock-initial-row">
                                    <td colspan="4" class="p-8 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="icon-package text-3xl text-gray-300 dark:text-gray-600"></span>
                                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">اختر المستودع المشحن أولاً بالأعلى</span>
                                            <span class="text-[11px] text-gray-500">سيتم عرض قائمة كافة المنتجات التي تملك رصيداً متاحاً تلقائياً هنا</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- LEFT PANEL: Manifest Parcel Items --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center justify-between flex-wrap gap-2 border-b pb-3 border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">
                                محتويات طرد المانيفست المشحونة
                            </h3>
                            <span id="parcel-count-badge" class="px-2 py-0.5 text-xs rounded-full font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                                0 أصناف
                            </span>
                        </div>

                        <button type="button" id="clear-parcel-btn" onclick="window.clearParcel()" class="text-xs text-rose-600 hover:text-rose-700 hover:underline hidden font-bold">
                            تفريغ الطرد ✕
                        </button>
                    </div>

                    {{-- Parcel Items Table Container --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-[540px] overflow-y-auto">
                        <table class="w-full text-xs text-right border-collapse" id="parcel-items-table">
                            <thead class="bg-gray-50 dark:bg-gray-800/80 text-gray-600 dark:text-gray-300 sticky top-0 z-10 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-2.5 w-10 text-center">#</th>
                                    <th class="p-2.5 w-40 md:w-48 max-w-[200px]">المنتج</th>
                                    <th class="p-2.5 w-20 text-center">المتوفر</th>
                                    <th class="p-2.5 w-36 text-center">الكمية المشحونة</th>
                                    <th class="p-2.5 w-12 text-center">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="parcel-table-body" class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr id="parcel-empty-row">
                                    <td colspan="5" class="p-8 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <span class="icon-truck text-3xl text-gray-300 dark:text-gray-600"></span>
                                            <span class="font-medium text-sm text-gray-700 dark:text-gray-300">طرد المانيفست فارغ حالياً</span>
                                            <span class="text-[11px] text-gray-500">انقر على زر "+ إضافة" بجانب أي صنف في جدول المخزون لإضافته مباشرة</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Parcel Summary Footer --}}
                    <div id="parcel-summary-box" class="p-3 bg-emerald-50/70 dark:bg-emerald-950/30 rounded-lg border border-emerald-200 dark:border-emerald-800/50 flex items-center justify-between text-xs font-bold text-emerald-900 dark:text-emerald-200">
                        <div class="flex items-center gap-2">
                            <span>إجمالي الأصناف:</span>
                            <span id="summary-items-count" class="text-sm font-mono">0</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>إجمالي القطع المشحونة:</span>
                            <span id="summary-units-count" class="text-sm font-mono text-emerald-700 dark:text-emerald-300">0 قطعة</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 3. Notes & Submission Card --}}
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                        {{ trans('inventory::app.admin.transfers.notes') }}
                    </label>
                    <textarea name="notes" rows="2" placeholder="أي ملاحظات خاصة بنقل الشحنة، محتويات الطرود، أو تعليمات التسليم..." class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800 flex-wrap gap-3">
                    <div class="text-xs text-gray-500 flex items-center gap-1.5">
                        <span class="icon-information text-base text-blue-500"></span>
                        سيتم حفظ المانيفست في حالة <strong>مسودة (Draft)</strong> تتيح لك مراجعته، اعتماده وإرساله، أو إلغائه لاحقاً.
                    </div>

                    <button type="submit" id="submit-manifest-btn" class="primary-button text-sm py-2.5 px-6 flex items-center gap-2 shadow-sm font-bold">
                        <span class="icon-done text-lg"></span>
                        <span id="submit-btn-text">حفظ واعتماد مسودة المانيفست</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    @pushOnce('scripts')
        <script>
            (function () {
                // Global state
                window.allAvailableProducts = [];
                window.addedParcelItems = new Map();

                const getEl = (id) => document.getElementById(id);

                // Handle Source Warehouse Selection
                window.handleSourceWarehouseChange = function (sourceId) {
                    console.log('[TransferManifest] Source warehouse selected:', sourceId);
                    if (!sourceId) {
                        const sel = getEl('source-warehouse-select');
                        sourceId = sel ? sel.value : null;
                    }

                    if (!sourceId) {
                        resetStockTable('اختر المستودع المشحن أولاً بالأعلى');
                        return;
                    }

                    if (window.addedParcelItems.size > 0) {
                        if (confirm('تغيير المستودع المشحن سيقوم بإفراغ محتويات الطرد الحالية للتحقق من أرصدة المستودع الجديد. هل تريد المتابعة؟')) {
                            window.addedParcelItems.clear();
                            window.renderParcelTable();
                        }
                    }

                    loadSourceProducts(sourceId);
                };

                window.refreshSourceProducts = function () {
                    const sel = getEl('source-warehouse-select');
                    const sourceId = sel ? sel.value : null;
                    if (!sourceId) {
                        alert('يرجى اختيار المستودع المشحن أولاً بالأعلى.');
                        if (sel) sel.focus();
                        return;
                    }
                    loadSourceProducts(sourceId);
                };

                function loadSourceProducts(sourceId) {
                    const stockBody = getEl('stock-table-body');
                    if (stockBody) {
                        stockBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg class="animate-spin h-6 w-6 text-blue-600" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span class="font-medium text-xs">جارٍ استدعاء كافة بضائع المخزن المتوفرة...</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }

                    const baseUrl = "{{ url(config('app.admin_url', 'admin') . '/inventory/transfers/source-products') }}";
                    const url = baseUrl + '/' + sourceId;
                    console.log('[TransferManifest] Fetching products from:', url);

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
                    .then(products => {
                        console.log('[TransferManifest] Loaded products count:', products ? products.length : 0);
                        window.allAvailableProducts = products || [];
                        const filterInput = getEl('stock-filter-input');
                        if (filterInput) filterInput.value = '';

                        window.renderStockTable(window.allAvailableProducts);

                        const badge = getEl('available-count-badge');
                        const addAllBtn = getEl('add-all-btn');

                        if (badge) {
                            if (window.allAvailableProducts.length > 0) {
                                badge.textContent = `${window.allAvailableProducts.length} صنف متوفر`;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }

                        if (addAllBtn) {
                            if (window.allAvailableProducts.length > 0) {
                                addAllBtn.classList.remove('hidden');
                            } else {
                                addAllBtn.classList.add('hidden');
                            }
                        }
                    })
                    .catch(err => {
                        console.error('[TransferManifest] Error loading products:', err);
                        const stockBody = getEl('stock-table-body');
                        if (stockBody) {
                            stockBody.innerHTML = `
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-rose-500 text-xs">
                                        حدث خطأ أثناء تحميل بضائع المستودع (${err.message}). يرجى النقر على زر 🔄 تحديث للمحاولة مجدداً.
                                    </td>
                                </tr>
                            `;
                        }
                    });
                }

                function resetStockTable(msg) {
                    window.allAvailableProducts = [];
                    const badge = getEl('available-count-badge');
                    const addAllBtn = getEl('add-all-btn');
                    if (badge) badge.classList.add('hidden');
                    if (addAllBtn) addAllBtn.classList.add('hidden');

                    const stockBody = getEl('stock-table-body');
                    if (stockBody) {
                        stockBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="icon-package text-3xl text-gray-300 dark:text-gray-600"></span>
                                        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">${msg}</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                }

                // Filter products locally as user types
                window.filterStockProducts = function (term) {
                    term = (term || '').trim().toLowerCase();
                    if (!term) {
                        window.renderStockTable(window.allAvailableProducts);
                        return;
                    }

                    const filtered = window.allAvailableProducts.filter(p => {
                        return (p.name && p.name.toLowerCase().includes(term)) ||
                               (p.sku && p.sku.toLowerCase().includes(term));
                    });

                    window.renderStockTable(filtered, true);
                };

                // Render Stock Table
                window.renderStockTable = function (products, isFilter = false) {
                    const stockBody = getEl('stock-table-body');
                    if (!stockBody) return;

                    stockBody.innerHTML = '';

                    if (!products || products.length === 0) {
                        stockBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400 text-xs">
                                    ${isFilter ? 'لا توجد بضائع تطابق كلمة البحث الحالية.' : 'لا توجد بضائع تملك رصيداً متاحاً في هذا المستودع حالياً.'}
                                </td>
                            </tr>
                        `;
                        return;
                    }

                    products.forEach(p => {
                        const isAdded = window.addedParcelItems.has(p.product_id);
                        const addedItem = isAdded ? window.addedParcelItems.get(p.product_id) : null;
                        const addedQty = addedItem ? addedItem.qty_shipped : 0;

                        const tr = document.createElement('tr');
                        tr.className = `hover:bg-blue-50/50 dark:hover:bg-gray-800/50 transition-colors ${isAdded ? 'bg-blue-50/30 dark:bg-blue-950/20' : ''}`;
                        tr.id = `stock-row-${p.product_id}`;

                        const imgHtml = p.image_url
                            ? `<img src="${p.image_url}" class="w-9 h-9 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0" alt="">`
                            : `<div class="w-9 h-9 rounded-md bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 font-bold text-xs shrink-0"><span class="icon-product"></span></div>`;

                        tr.innerHTML = `
                            <td class="p-2.5 w-44 md:w-52 max-w-[220px]">
                                <div class="flex items-center gap-2">
                                    ${imgHtml}
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-xs text-gray-900 dark:text-white leading-snug whitespace-normal break-words line-clamp-2 hover:line-clamp-none transition-all cursor-pointer" title="${p.name}">${p.name}</span>
                                        <span class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: ${p.sku}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-mono">
                                    ${p.available_qty}
                                </span>
                            </td>
                            <td class="p-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.stepStockQty(${p.product_id}, -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold">-</button>
                                    <input type="number" id="stock-qty-input-${p.product_id}" class="w-12 px-1 py-0.5 text-center font-bold rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs" value="1" min="1" max="${p.available_qty}">
                                    <button type="button" onclick="window.stepStockQty(${p.product_id}, 1, ${p.available_qty})" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold">+</button>
                                    <button type="button" onclick="window.setStockQtyMax(${p.product_id}, ${p.available_qty})" class="px-1.5 py-0.5 rounded text-[10px] bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-bold" title="تحديد كامل الرصيد المتوفر">الكل</button>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <button type="button" 
                                        id="add-btn-${p.product_id}"
                                        onclick="window.handleAddStockItem(${p.product_id})"
                                        class="px-2.5 py-1 rounded text-xs font-bold transition-all shadow-sm ${isAdded ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'primary-button'}">
                                    ${isAdded ? `مضاف (${addedQty}) +` : '+ إضافة'}
                                </button>
                            </td>
                        `;

                        stockBody.appendChild(tr);
                    });
                };

                // Helper steppers on stock row
                window.stepStockQty = function (productId, delta, max) {
                    const input = getEl(`stock-qty-input-${productId}`);
                    if (!input) return;
                    let val = (parseInt(input.value) || 1) + delta;
                    if (val < 1) val = 1;
                    if (max && val > max) val = max;
                    input.value = val;
                };

                window.setStockQtyMax = function (productId, max) {
                    const input = getEl(`stock-qty-input-${productId}`);
                    if (input) input.value = max;
                };

                window.handleAddStockItem = function (productId) {
                    const product = window.allAvailableProducts.find(p => p.product_id === productId);
                    if (!product) return;

                    const input = getEl(`stock-qty-input-${productId}`);
                    const qtyToAdd = input ? (parseInt(input.value) || 1) : 1;

                    window.addItemToParcel(product, qtyToAdd);
                };

                // Add single item to parcel map
                window.addItemToParcel = function (product, qtyToAdd) {
                    if (window.addedParcelItems.has(product.product_id)) {
                        const item = window.addedParcelItems.get(product.product_id);
                        const newQty = item.qty_shipped + qtyToAdd;
                        if (newQty > product.available_qty) {
                            item.qty_shipped = product.available_qty;
                            alert(`تم ضبط الكمية على أقصى رصيد متاح (${product.available_qty} قطعة) للصنف ${product.sku}.`);
                        } else {
                            item.qty_shipped = newQty;
                        }
                    } else {
                        const initialQty = Math.min(qtyToAdd, product.available_qty);
                        window.addedParcelItems.set(product.product_id, {
                            product_id: product.product_id,
                            sku: product.sku,
                            name: product.name,
                            available_qty: product.available_qty,
                            qty_shipped: initialQty,
                            image_url: product.image_url
                        });
                    }

                    window.renderParcelTable();
                    window.updateStockRowStatus(product.product_id);
                };

                // Update badge on stock row
                window.updateStockRowStatus = function (productId) {
                    const tr = getEl(`stock-row-${productId}`);
                    const btnAdd = getEl(`add-btn-${productId}`);
                    if (!btnAdd) return;

                    const isAdded = window.addedParcelItems.has(productId);

                    if (isAdded) {
                        const item = window.addedParcelItems.get(productId);
                        btnAdd.textContent = `مضاف (${item.qty_shipped}) +`;
                        btnAdd.className = 'px-2.5 py-1 rounded text-xs font-bold transition-all shadow-sm bg-blue-600 hover:bg-blue-700 text-white';
                        if (tr) tr.classList.add('bg-blue-50/30', 'dark:bg-blue-950/20');
                    } else {
                        btnAdd.textContent = '+ إضافة';
                        btnAdd.className = 'px-2.5 py-1 rounded text-xs font-bold transition-all shadow-sm primary-button';
                        if (tr) tr.classList.remove('bg-blue-50/30', 'dark:bg-blue-950/20');
                    }
                };

                // Add all stock
                window.addAllStockToParcel = function () {
                    if (!window.allAvailableProducts || window.allAvailableProducts.length === 0) return;

                    if (!confirm(`هل أنت متأكد من إضافة جميع الأصناف المتوفرة (${window.allAvailableProducts.length} صنف) إلى طرد المانيفست بكامل أرصدتها؟`)) {
                        return;
                    }

                    window.allAvailableProducts.forEach(p => {
                        window.addedParcelItems.set(p.product_id, {
                            product_id: p.product_id,
                            sku: p.sku,
                            name: p.name,
                            available_qty: p.available_qty,
                            qty_shipped: p.available_qty,
                            image_url: p.image_url
                        });
                    });

                    window.renderParcelTable();
                    window.renderStockTable(window.allAvailableProducts);
                };

                // Clear entire parcel
                window.clearParcel = function () {
                    if (confirm('هل أنت متأكد من تفريغ كافة محتويات الطرد؟')) {
                        window.addedParcelItems.clear();
                        window.renderParcelTable();
                        window.renderStockTable(window.allAvailableProducts);
                    }
                };

                // Render Left Table: Manifest Parcel Contents
                window.renderParcelTable = function () {
                    const parcelBody = getEl('parcel-table-body');
                    const parcelBadge = getEl('parcel-count-badge');
                    const clearBtn = getEl('clear-parcel-btn');
                    if (!parcelBody) return;

                    parcelBody.innerHTML = '';

                    if (window.addedParcelItems.size === 0) {
                        parcelBody.innerHTML = `
                            <tr id="parcel-empty-row">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="icon-truck text-3xl text-gray-300 dark:text-gray-600"></span>
                                        <span class="font-medium text-sm text-gray-700 dark:text-gray-300">طرد المانيفست فارغ حالياً</span>
                                        <span class="text-[11px] text-gray-500">انقر على زر "+ إضافة" بجانب أي صنف في جدول المخزون لإضافته مباشرة</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                        if (parcelBadge) parcelBadge.textContent = '0 أصناف';
                        if (clearBtn) clearBtn.classList.add('hidden');
                        window.updateSummary();
                        return;
                    }

                    if (clearBtn) clearBtn.classList.remove('hidden');
                    if (parcelBadge) parcelBadge.textContent = `${window.addedParcelItems.size} أصناف`;

                    let index = 0;
                    window.addedParcelItems.forEach((item, productId) => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors';

                        const imgHtml = item.image_url
                            ? `<img src="${item.image_url}" class="w-8 h-8 rounded-md object-cover border border-gray-200 dark:border-gray-700 shrink-0" alt="">`
                            : `<div class="w-8 h-8 rounded-md bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 font-bold text-xs shrink-0">#</div>`;

                        tr.innerHTML = `
                            <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                            <input type="hidden" name="items[${index}][sku]" value="${item.sku}">
                            
                            <td class="p-2.5 text-center text-gray-400 font-mono">${index + 1}</td>
                            <td class="p-2.5 w-40 md:w-48 max-w-[200px]">
                                <div class="flex items-center gap-2">
                                    ${imgHtml}
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-xs text-gray-900 dark:text-white leading-snug whitespace-normal break-words line-clamp-2 hover:line-clamp-none transition-all cursor-pointer" title="${item.name}">${item.name}</span>
                                        <span class="text-[11px] text-gray-500 font-mono mt-0.5">SKU: ${item.sku}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <span class="px-1.5 py-0.5 rounded text-[11px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                                    ${item.available_qty}
                                </span>
                            </td>
                            <td class="p-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" onclick="window.stepParcelQty(${productId}, -1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold">-</button>
                                    <input type="number" 
                                           id="parcel-qty-input-${productId}"
                                           name="items[${index}][qty_shipped]" 
                                           value="${item.qty_shipped}" 
                                           min="1" 
                                           max="${item.available_qty}" 
                                           onchange="window.setParcelQty(${productId}, this.value)"
                                           class="w-16 px-1.5 py-1 text-center font-bold rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-xs focus:border-blue-500" 
                                           required>
                                    <button type="button" onclick="window.stepParcelQty(${productId}, 1)" class="px-1.5 py-0.5 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold">+</button>
                                    <span class="text-[11px] text-gray-400">قطعة</span>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <button type="button" onclick="window.removeParcelItem(${productId})" class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded transition-colors" title="حذف من الطرد">
                                    <span class="icon-cancel text-base"></span>
                                </button>
                            </td>
                        `;

                        parcelBody.appendChild(tr);
                        index++;
                    });

                    window.updateSummary();
                };

                window.stepParcelQty = function (productId, delta) {
                    const item = window.addedParcelItems.get(productId);
                    if (!item) return;
                    let val = item.qty_shipped + delta;
                    if (val < 1) val = 1;
                    if (val > item.available_qty) {
                        alert(`لا يمكن تجاوز الرصيد المتاح (${item.available_qty} قطعة).`);
                        return;
                    }
                    item.qty_shipped = val;
                    const input = getEl(`parcel-qty-input-${productId}`);
                    if (input) input.value = val;
                    window.updateSummary();
                    window.updateStockRowStatus(productId);
                };

                window.setParcelQty = function (productId, newQty) {
                    const item = window.addedParcelItems.get(productId);
                    if (!item) return;
                    let val = parseInt(newQty) || 1;
                    if (val < 1) val = 1;
                    if (val > item.available_qty) {
                        alert(`الكمية لا يمكن أن تتجاوز الرصيد المتوفر (${item.available_qty} قطعة).`);
                        val = item.available_qty;
                    }
                    item.qty_shipped = val;
                    const input = getEl(`parcel-qty-input-${productId}`);
                    if (input) input.value = val;
                    window.updateSummary();
                    window.updateStockRowStatus(productId);
                };

                window.removeParcelItem = function (productId) {
                    window.addedParcelItems.delete(productId);
                    window.renderParcelTable();
                    window.updateStockRowStatus(productId);
                };

                // Update summary footer
                window.updateSummary = function () {
                    let totalUnits = 0;
                    window.addedParcelItems.forEach(item => {
                        totalUnits += parseInt(item.qty_shipped || 0);
                    });

                    const itemsCountEl = getEl('summary-items-count');
                    const unitsCountEl = getEl('summary-units-count');
                    const submitTextEl = getEl('submit-btn-text');

                    if (itemsCountEl) itemsCountEl.textContent = window.addedParcelItems.size;
                    if (unitsCountEl) unitsCountEl.textContent = `${totalUnits} قطعة`;

                    if (submitTextEl) {
                        if (window.addedParcelItems.size > 0) {
                            submitTextEl.textContent = `حفظ واعتماد مسودة المانيفست (${window.addedParcelItems.size} أصناف / ${totalUnits} قطعة)`;
                        } else {
                            submitTextEl.textContent = 'حفظ واعتماد مسودة المانيفست';
                        }
                    }
                };

                // Form validation on submit
                const form = getEl('transfer-create-form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        const src = getEl('source-warehouse-select');
                        const dst = getEl('destination-warehouse-select');

                        if (src && dst && src.value && dst.value && src.value === dst.value) {
                            e.preventDefault();
                            alert('المستودع المصدر والمستودع الهدف يجب أن يكونا مختلفين.');
                            dst.focus();
                            return;
                        }

                        if (window.addedParcelItems.size === 0) {
                            e.preventDefault();
                            alert('يجب إضافة صنف واحد على الأقل إلى طرد المانيفست قبل الحفظ.');
                            return;
                        }
                    });
                }

                // Global fallback delegation on document
                document.addEventListener('change', function (e) {
                    if (e.target && (e.target.id === 'source-warehouse-select' || e.target.name === 'source_inventory_source_id')) {
                        window.handleSourceWarehouseChange(e.target.value);
                    }
                });

                // Auto initialize on load if warehouse is pre-selected
                function autoInit() {
                    const sel = getEl('source-warehouse-select');
                    if (sel && sel.value) {
                        console.log('[TransferManifest] Auto-init on load with source:', sel.value);
                        window.handleSourceWarehouseChange(sel.value);
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
