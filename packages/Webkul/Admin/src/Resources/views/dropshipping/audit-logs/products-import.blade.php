<x-admin::layouts>
    <x-slot:title>
        سجل تدقيق استيراد المنتجات — AliExpress Import Audit Log
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        @include('admin::dropshipping.audit-logs.tabs')

        {{-- Page Header --}}
        <div class="flex items-center justify-between max-sm:flex-col max-sm:items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    سجل تدقيق استيراد المنتجات (AliExpress Product Imports Audit)
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    تدقيق شامل ومفصل لجميع المنتجات المستوردة من علي إكسبرس، ببيانات التكلفة الأصلية، المتغيرات، والمواصفات والشحن.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dropshipping.import.index') }}" class="primary-button">
                    + استيراد منتج جديد
                </a>
            </div>
        </div>

        {{-- High-Level Statistics Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">إجمالي سجلات الاستيراد</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        <span class="icon-products text-xl"></span>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_imports']) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المنتجات النشطة بالمتجر</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400">
                        <span class="icon-done text-xl"></span>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-green-600">{{ number_format($stats['active_imports']) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المنتجات المحذوفة (أرشيف)</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <span class="icon-delete text-xl"></span>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-amber-600">{{ number_format($stats['deleted_imports']) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">المنتجات ببيانات شحن متزامنة</span>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                        <span class="icon-cart text-xl"></span>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-purple-600">{{ number_format($stats['with_shipping']) }}</p>
            </div>
        </div>

        {{-- Filter & Search Form --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <form method="GET" action="{{ route('admin.audit-logs.products-import.index') }}" class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-1 items-center gap-3 min-w-[280px]">
                    <div class="relative w-full max-w-md">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}" 
                            placeholder="بحث باسم المنتج، SKU، أو معرف علي إكسبرس..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                        <span class="icon-search absolute right-3 top-2.5 text-gray-400"></span>
                    </div>

                    <select 
                        name="status" 
                        class="rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">جميع الحالات</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>ناجح (Success)</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فاشل (Failed)</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المعالجة (Pending)</option>
                    </select>

                    <button type="submit" class="secondary-button">
                        بحث وتصفية
                    </button>

                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('admin.audit-logs.products-import.index') }}" class="text-sm text-gray-500 hover:text-red-500">
                            إلغاء التصفية
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Main Audit Table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-600 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300">
                            <th class="py-3 px-4"># المعرف</th>
                            <th class="py-3 px-4">المنتج والكتالوج المحلي</th>
                            <th class="py-3 px-4">معرف علي إكسبرس</th>
                            <th class="py-3 px-4">حالة الاستيراد</th>
                            <th class="py-3 px-4">تكلفة المورد الأصلية</th>
                            <th class="py-3 px-4">المتغيرات والمخزون</th>
                            <th class="py-3 px-4">شحن علي إكسبرس</th>
                            <th class="py-3 px-4">تاريخ الاستيراد</th>
                            <th class="py-3 px-4 text-center">البيانات الخام</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($imports as $log)
                            @php
                                $snapshot = $log->payload_snapshot ?? [];
                                $variants = $snapshot['variants'] ?? [];
                                $axes = $snapshot['axes'] ?? [];
                                $firstImg = $snapshot['image_urls'][0] ?? null;
                                
                                // Price range calculation from snapshot
                                $prices = array_filter(array_column($variants, 'price'));
                                $minPrice = !empty($prices) ? min($prices) : null;
                                $maxPrice = !empty($prices) ? max($prices) : null;
                                $totalStock = array_sum(array_column($variants, 'stock'));
                            @endphp

                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                {{-- 1. ID --}}
                                <td class="py-3 px-4 font-mono font-bold text-gray-700 dark:text-gray-300">
                                    #{{ $log->id }}
                                </td>

                                {{-- 2. Product Thumbnail & Catalog Info --}}
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        @if($firstImg)
                                            <img src="{{ $firstImg }}" alt="Product Image" title="{{ $log->product_name ?? $log->product?->name ?? $snapshot['title'] ?? 'منتج' }}" class="h-12 w-12 flex-shrink-0 rounded-lg object-cover border border-gray-200 dark:border-gray-700 shadow-sm">
                                        @else
                                            <div class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                                                <span class="icon-products text-lg"></span>
                                            </div>
                                        @endif

                                        <div class="flex flex-col gap-1 text-xs font-mono">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-gray-800 dark:text-gray-200">SKU: {{ $log->catalog_sku ?? $log->sku ?? '—' }}</span>
                                                @if($log->product_id)
                                                    <span class="text-blue-600 dark:text-blue-400 font-bold">PID: #{{ $log->product_id }}</span>
                                                @endif
                                            </div>

                                            <div>
                                                <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 text-[11px]">
                                                    {{ $log->type ?? 'configurable' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. AliExpress / Source Product ID --}}
                                <td class="py-3 px-4 font-mono text-xs">
                                    @if(str_starts_with((string)$log->aliexpress_product_id, 'CSV-'))
                                        <span class="inline-flex items-center gap-1 text-gray-700 dark:text-gray-300 font-semibold" title="تم الاستيراد عبر ملف CSV DataTransfer">
                                            <span>{{ $log->aliexpress_product_id }}</span>
                                            <span class="px-1 py-0.2 text-[10px] rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">CSV</span>
                                        </span>
                                    @else
                                        <a 
                                            href="https://www.aliexpress.com/item/{{ $log->aliexpress_product_id }}.html" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
                                            title="فتح المنتج في علي إكسبرس"
                                        >
                                            <span>{{ $log->aliexpress_product_id }}</span>
                                            <span class="icon-external text-xs"></span>
                                        </a>
                                    @endif
                                </td>

                                {{-- 4. Status --}}
                                <td class="py-3 px-4">
                                    @php
                                        $isDeletedFromCatalog = str_contains(strtolower($log->error ?? ''), 'no longer exists') 
                                            || str_contains(strtolower($log->error ?? ''), 'deleted')
                                            || ($log->product_id && empty($log->catalog_sku));
                                    @endphp

                                    @if($isDeletedFromCatalog)
                                        <div class="flex flex-col gap-0.5">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300" title="تم حذف هذا المنتج من المتجر سابقاً بعد استيراده">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                تم حذف المنتج من المتجر
                                            </span>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                (سجل أرشفة تاريخي)
                                            </span>
                                        </div>
                                    @elseif($log->status === 'success')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                            نشط بالمتجر
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <div class="flex flex-col">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                فشل الاستيراد
                                            </span>
                                            @if($log->error)
                                                <span class="mt-1 max-w-[180px] truncate text-xs text-red-500" title="{{ $log->error }}">
                                                    {{ $log->error }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                            {{ $log->status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- 5. Supplier Cost --}}
                                <td class="py-3 px-4 font-mono text-xs">
                                    @if($minPrice !== null)
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            @if($minPrice == $maxPrice)
                                                ${{ number_format($minPrice, 2) }}
                                            @else
                                                ${{ number_format($minPrice, 2) }} – ${{ number_format($maxPrice, 2) }}
                                            @endif
                                        </div>
                                        <span class="text-gray-500 text-[10px]">USD (Net Source)</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- 6. Variants & Stock --}}
                                <td class="py-3 px-4 text-xs">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $log->variants_count ?? count($variants) }} نوع / خيار
                                    </div>
                                    <div class="text-gray-500 font-mono text-[11px]">
                                        المخزون: {{ number_format($totalStock) }}
                                    </div>
                                    @if(!empty($axes))
                                        <div class="mt-1 flex flex-wrap gap-1 text-[10px]">
                                            @foreach(array_keys($axes) as $axis)
                                                <span class="rounded bg-gray-100 px-1 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $axis }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                {{-- 7. AliExpress Shipping Data --}}
                                <td class="py-3 px-4 text-xs">
                                    @if($log->base_shipping_cost !== null)
                                        @php
                                            $shippingCompany = $log->shipping_company ?? '';
                                            $isChoice = stripos($shippingCompany, 'selection') !== false 
                                                || stripos($shippingCompany, 'choice') !== false 
                                                || !empty($snapshot['is_choice']) 
                                                || !empty($snapshot['shipping']['is_choice']);
                                        @endphp

                                        <div class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                            ${{ number_format($log->base_shipping_cost, 2) }} {{ $log->shipping_currency ?? 'USD' }}
                                        </div>

                                        @if($isChoice)
                                            <div class="my-1 inline-flex items-center gap-1 rounded bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 border border-amber-300 dark:border-amber-700 shadow-xs" title="خدمة شحن Choice والتزام AliExpress المضمون">
                                                <span class="rounded bg-amber-400 px-1 py-0.2 text-[9px] font-black text-black leading-tight">Choice</span>
                                                <span class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400">التزام AliExpress</span>
                                            </div>
                                        @endif

                                        <div class="text-[11px] text-gray-600 dark:text-gray-300 truncate max-w-[150px]" title="{{ $log->shipping_company }}">
                                            {{ $log->shipping_company ?? 'AliExpress Shipping' }}
                                        </div>
                                        <div class="text-[10px] text-gray-500">
                                            {{ $log->shipping_min_days ?? '?' }}-{{ $log->shipping_max_days ?? '?' }} يوم 
                                            @if($log->shipping_tracking)
                                                • تتبع ✅
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">غير متزامن</span>
                                    @endif
                                </td>

                                {{-- 8. Timestamps --}}
                                <td class="py-3 px-4 font-mono text-xs text-gray-500">
                                    <div>{{ $log->created_at?->format('Y-m-d') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $log->created_at?->format('H:i:s') }}</div>
                                </td>

                                {{-- 9. Actions / Raw Snapshot Modal Trigger --}}
                                <td class="py-3 px-4 text-center">
                                    <button 
                                        type="button" 
                                        onclick="openSnapshotModal({{ $log->id }})"
                                        class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                        title="عرض كامل بيانات الاستيراد الخام"
                                    >
                                        <span class="icon-view text-sm"></span>
                                        <span>التفاصيل</span>
                                    </button>

                                    {{-- Hidden Snapshot Data Container (Safe for Vue 3 DOM hydration) --}}
                                    <script type="application/json" id="snapshot-data-{{ $log->id }}">
                                        {!! json_encode([
                                            'id' => $log->id,
                                            'product_name' => $log->product_name ?? $log->product?->name ?? $snapshot['title'] ?? 'منتج',
                                            'sku' => $log->catalog_sku ?? $log->sku,
                                            'aliexpress_id' => $log->aliexpress_product_id,
                                            'status' => $log->status,
                                            'shipping_cost' => $log->base_shipping_cost,
                                            'shipping_company' => $log->shipping_company,
                                            'is_choice' => $isChoice ?? false,
                                            'shipping_window' => ($log->shipping_min_days ?? '?') . ' - ' . ($log->shipping_max_days ?? '?') . ' days',
                                            'variants' => $variants,
                                            'axes' => $axes,
                                            'seo' => [
                                                'meta_title' => $log->meta_title ?? $snapshot['meta_title'] ?? null,
                                                'meta_keywords' => $log->meta_keywords ?? $snapshot['meta_keywords'] ?? null,
                                                'meta_description' => $log->meta_description ?? $snapshot['meta_description'] ?? null,
                                            ],
                                            'raw_json' => $snapshot,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
                                    </script>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    لا توجد سجلات استيراد مطابقة للبحث.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                {{ $imports->links() }}
            </div>
        </div>
    </div>

    {{-- Snapshot Inspection Modal --}}
    <div id="snapshotModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm transition-opacity items-center justify-center p-4">
        <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modalTitle">
                        تفاصيل سجل الاستيراد الخام
                    </h3>
                    <p class="text-xs text-gray-500 font-mono" id="modalSubtitle"></p>
                </div>
                <button 
                    type="button" 
                    onclick="closeSnapshotModal()" 
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <span class="icon-cancel text-xl"></span>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                {{-- Quick Summary --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800/60 p-4 rounded-xl text-xs font-mono">
                    <div>
                        <span class="text-gray-500 block">AliExpress ID:</span>
                        <span class="font-bold text-gray-900 dark:text-white" id="modalAeId"></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Catalog SKU:</span>
                        <span class="font-bold text-gray-900 dark:text-white" id="modalSku"></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">الشحن الأساسي:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400" id="modalShipping"></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block">شركة التوصيل:</span>
                        <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                            <span class="font-bold text-gray-900 dark:text-white truncate block" id="modalCompany"></span>
                            <span id="modalChoiceBadge" style="display: none;" class="inline-flex items-center gap-1 rounded bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 border border-amber-300 dark:border-amber-700 shadow-xs">
                                <span class="rounded bg-amber-400 px-1 py-0.2 text-[9px] font-black text-black leading-tight">Choice</span>
                                <span class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400">التزام AliExpress</span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- JSON Code Viewer --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-bold text-gray-800 dark:text-white">
                            البيانات البرمجية الخام (Payload Snapshot JSON):
                        </label>
                        <button 
                            type="button" 
                            onclick="copyModalJson()" 
                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold"
                        >
                            نسخ الكود الكامل (Copy JSON)
                        </button>
                    </div>
                    <pre id="modalJsonContent" class="h-96 overflow-auto rounded-xl bg-gray-950 p-4 text-xs font-mono text-emerald-400 border border-gray-800 text-left" dir="ltr"></pre>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end border-t border-gray-200 px-6 py-3 dark:border-gray-800">
                <button type="button" onclick="closeSnapshotModal()" class="secondary-button">
                    إغلاق النافذة
                </button>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script>
            window.currentModalJsonString = '';

            window.openSnapshotModal = function(importId) {
                const el = document.getElementById('snapshot-data-' + importId);
                if (!el) {
                    console.error('Snapshot script element not found for import ID:', importId);
                    return;
                }

                try {
                    const data = JSON.parse(el.textContent);
                    window.currentModalJsonString = JSON.stringify(data.raw_json || {}, null, 2);

                    document.getElementById('modalTitle').innerText = data.product_name || 'سجل الاستيراد #' + data.id;
                    document.getElementById('modalSubtitle').innerText = 'سجل رقم #' + data.id + ' | رمز: ' + (data.sku || '—');
                    document.getElementById('modalAeId').innerText = data.aliexpress_id || '—';
                    document.getElementById('modalSku').innerText = data.sku || '—';
                    document.getElementById('modalShipping').innerText = data.shipping_cost ? '$' + Number(data.shipping_cost).toFixed(2) : 'غير متوفر';
                    document.getElementById('modalCompany').innerText = data.shipping_company || '—';
                    
                    const choiceBadge = document.getElementById('modalChoiceBadge');
                    if (choiceBadge) {
                        choiceBadge.style.display = data.is_choice ? 'inline-flex' : 'none';
                    }

                    document.getElementById('modalJsonContent').innerText = window.currentModalJsonString;

                    const modal = document.getElementById('snapshotModal');
                    if (modal) {
                        modal.style.display = 'flex';
                    }
                } catch (e) {
                    console.error('Error parsing snapshot JSON data:', e);
                }
            };

            window.closeSnapshotModal = function() {
                const modal = document.getElementById('snapshotModal');
                if (modal) {
                    modal.style.display = 'none';
                }
            };

            window.copyModalJson = function() {
                if (!window.currentModalJsonString) return;
                navigator.clipboard.writeText(window.currentModalJsonString).then(() => {
                    alert('تم نسخ كود الـ JSON بنجاح.');
                });
            };
        </script>
    @endpushOnce
</x-admin::layouts>
