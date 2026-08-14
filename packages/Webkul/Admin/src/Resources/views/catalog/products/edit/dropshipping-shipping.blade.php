@php
    $snapshot = is_array($import->payload_snapshot)
        ? $import->payload_snapshot
        : (json_decode((string) $import->payload_snapshot, true) ?? []);

    $shippingCompany = $import->shipping_company ?? '';
    $isChoice = (bool) (
        stripos($shippingCompany, 'selection') !== false 
        || stripos($shippingCompany, 'choice') !== false 
        || !empty($snapshot['is_choice']) 
        || !empty($snapshot['shipping']['is_choice'])
    );

    // Calculate supplier cost from variants / snapshot
    $supplierMinPrice = null;
    $supplierMaxPrice = null;
    if (!empty($snapshot['variants']) && is_array($snapshot['variants'])) {
        $prices = array_filter(array_column($snapshot['variants'], 'price'), 'is_numeric');
        if (!empty($prices)) {
            $supplierMinPrice = min($prices);
            $supplierMaxPrice = max($prices);
        }
    }

    $baseShipping = $import->base_shipping_cost !== null ? (float) $import->base_shipping_cost : null;
    $storePrice = (float) $product->price;

    $totalLandedCostMin = $supplierMinPrice !== null && $baseShipping !== null ? $supplierMinPrice + $baseShipping : null;
    $totalLandedCostMax = $supplierMaxPrice !== null && $baseShipping !== null ? $supplierMaxPrice + $baseShipping : null;
@endphp

<div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 transition-all">
    {{-- Header --}}
    <div class="flex items-center justify-between gap-2 border-b border-gray-150 dark:border-gray-800 pb-3 mb-3">
        <div class="flex items-center gap-2">
            <span class="icon-shipping text-xl text-blue-600 dark:text-blue-400"></span>
            <div>
                <p class="text-base font-bold text-gray-800 dark:text-white">
                    تكلفة شحن المورد (AliExpress)
                </p>
                <p class="text-[11px] text-gray-500">
                    بيانات داخلية خاصة بالإدارة فقط (غير ظاهرة للعميل)
                </p>
            </div>
        </div>

        {{-- Live Sync Button --}}
        @if(!str_starts_with((string)$import->aliexpress_product_id, 'CSV-'))
            <button 
                type="button" 
                onclick="syncProductEditShipping({{ $import->id }}, this)" 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 shadow-xs transition-all cursor-pointer"
                title="إعادة فحص وتحديث بيانات الشحن من علي إكسبرس فوراً"
            >
                <span class="icon-refresh text-xs"></span>
                <span>تحديث الشحن</span>
            </button>
        @endif
    </div>

    {{-- Body Content --}}
    <div class="space-y-3 text-xs">
        {{-- 1. Source Link & Badges --}}
        <div class="flex items-center justify-between flex-wrap gap-2 bg-gray-50 dark:bg-gray-800/60 p-2.5 rounded-lg">
            <div class="flex items-center gap-1.5">
                <span class="text-gray-500">معرف علي إكسبرس:</span>
                <a 
                    href="https://www.aliexpress.com/item/{{ $import->aliexpress_product_id }}.html" 
                    target="_blank" 
                    class="font-mono font-bold text-blue-600 hover:underline dark:text-blue-400 inline-flex items-center gap-1"
                    title="فتح صفحة المنتج الأصلية في AliExpress"
                >
                    <span>{{ $import->aliexpress_product_id }}</span>
                    <span class="icon-external text-[10px]"></span>
                </a>
            </div>

            @if($isChoice)
                <span class="inline-flex items-center gap-1 rounded bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 border border-amber-300 dark:border-amber-700 shadow-xs">
                    <span class="rounded bg-amber-400 px-1 py-0.2 text-[9px] font-black text-black leading-tight">Choice</span>
                    <span class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400">التزام AliExpress</span>
                </span>
            @endif
        </div>

        {{-- 2. Shipping Details Grid --}}
        <div class="grid grid-cols-2 gap-2.5">
            {{-- Shipping Cost --}}
            <div class="p-2.5 rounded-lg border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                <span class="text-gray-500 block mb-1">تكلفة الشحن:</span>
                @if($baseShipping !== null)
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-sm">
                        ${{ number_format($baseShipping, 2) }} {{ $import->shipping_currency ?? 'USD' }}
                    </span>
                @else
                    <span class="text-gray-400 font-medium">غير متزامن</span>
                @endif
            </div>

            {{-- Carrier & Window --}}
            <div class="p-2.5 rounded-lg border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                <span class="text-gray-500 block mb-1">شركة التوصيل والمدة:</span>
                <span class="font-bold text-gray-800 dark:text-gray-200 block truncate" title="{{ $import->shipping_company }}">
                    {{ $import->shipping_company ?? '—' }}
                </span>
                <span class="text-[10px] text-gray-500">
                    {{ $import->shipping_min_days ?? '?' }}-{{ $import->shipping_max_days ?? '?' }} يوم 
                    @if($import->shipping_tracking)
                        • تتبع ✅
                    @endif
                </span>
            </div>
        </div>

        {{-- 3. Landed Cost & Margin Breakdown --}}
        <div class="p-3 rounded-lg bg-blue-50/50 dark:bg-blue-950/30 border border-blue-150 dark:border-blue-900/40 space-y-1.5">
            <p class="font-bold text-gray-800 dark:text-gray-200 mb-1 text-[11px]">
                📊 تحليل التكلفة وهامش الربح التقديري:
            </p>

            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                <span>سعر المنتج من المورد:</span>
                <span class="font-mono font-semibold">
                    @if($supplierMinPrice !== null)
                        ${{ number_format($supplierMinPrice, 2) }}
                        @if($supplierMaxPrice !== null && $supplierMaxPrice > $supplierMinPrice)
                            - ${{ number_format($supplierMaxPrice, 2) }}
                        @endif
                    @else
                        —
                    @endif
                </span>
            </div>

            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                <span>تكلفة الشحن (AliExpress):</span>
                <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                    {{ $baseShipping !== null ? '+$' . number_format($baseShipping, 2) : '—' }}
                </span>
            </div>

            <div class="flex justify-between border-t border-blue-200 dark:border-blue-800 pt-1 font-bold text-gray-900 dark:text-white">
                <span>إجمالي تكلفة الشراء عليك:</span>
                <span class="font-mono text-blue-700 dark:text-blue-300">
                    @if($totalLandedCostMin !== null)
                        ${{ number_format($totalLandedCostMin, 2) }}
                        @if($totalLandedCostMax !== null && $totalLandedCostMax > $totalLandedCostMin)
                            - ${{ number_format($totalLandedCostMax, 2) }}
                        @endif
                    @else
                        —
                    @endif
                </span>
            </div>

            <div class="flex justify-between pt-0.5 text-gray-600 dark:text-gray-300 text-[11px]">
                <span>سعر البيع الحالي في المتجر:</span>
                <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                    ${{ number_format($storePrice, 2) }}
                </span>
            </div>
        </div>

        {{-- 4. Last Sync Timestamp --}}
        @if($import->shipping_synced_at)
            <div class="text-[10px] text-gray-400 text-left font-mono">
                آخر فحص للشحن: {{ $import->shipping_synced_at }}
            </div>
        @endif
    </div>
</div>

@pushOnce('scripts')
    <script>
        window.syncProductEditShipping = function(importId, btnEl) {
            if (!importId || !btnEl) return;
            
            const icon = btnEl.querySelector('.icon-refresh') || btnEl;
            const origClass = icon.className;
            
            btnEl.disabled = true;
            icon.className = 'icon-refresh text-xs animate-spin inline-block text-blue-600';
            
            const syncUrl = "{{ route('admin.audit-logs.products-import.sync-shipping', ['id' => ':id']) }}".replace(':id', importId);

            fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                if (res.status === 200 && res.body.success) {
                    if (window.emitter) {
                        window.emitter.emit('add-flash', { type: 'success', message: res.body.message || 'تمت مزامنة بيانات الشحن بنجاح.' });
                    }
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (window.emitter) {
                        window.emitter.emit('add-flash', { type: 'warning', message: res.body.message || 'تعذر العثور على خيارات شحن متاحة لهذا المنتج حالياً.' });
                    } else {
                        alert(res.body.message || 'تعذر العثور على خيارات شحن متاحة لهذا المنتج حالياً.');
                    }
                    btnEl.disabled = false;
                    icon.className = origClass;
                }
            })
            .catch(err => {
                console.error('Shipping sync error:', err);
                if (window.emitter) {
                    window.emitter.emit('add-flash', { type: 'error', message: 'حدث خطأ في الاتصال أثناء مزامنة الشحن.' });
                } else {
                    alert('حدث خطأ في الاتصال أثناء مزامنة الشحن.');
                }
                btnEl.disabled = false;
                icon.className = origClass;
            });
        };
    </script>
@endpushOnce
