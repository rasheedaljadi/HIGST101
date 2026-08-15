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

    // Determine if we are editing a specific child variant
    $isVariant = !empty($product->parent_id);

    $supplierMinPrice = null;
    $supplierMaxPrice = null;

    if ($isVariant) {
        // 1. If editing a specific child variant, fetch its exact acquisition cost
        $offer = \App\Models\HigestSourceOffer::where('variant_id', $product->id)->first();
        if ($offer && $offer->acquisition_cost !== null) {
            $supplierMinPrice = (float) $offer->acquisition_cost;
            $supplierMaxPrice = (float) $offer->acquisition_cost;
        } elseif ($product->cost !== null && is_numeric($product->cost)) {
            $supplierMinPrice = (float) $product->cost;
            $supplierMaxPrice = (float) $product->cost;
        }
    } else {
        // 2. If editing a parent configurable product with variants, calculate min-max from child variants
        if ($product->variants && $product->variants->isNotEmpty()) {
            $variantIds = $product->variants->pluck('id')->all();
            $offerCosts = \App\Models\HigestSourceOffer::whereIn('variant_id', $variantIds)
                ->whereNotNull('acquisition_cost')
                ->pluck('acquisition_cost')
                ->map(fn($v) => (float) $v)
                ->all();

            if (!empty($offerCosts)) {
                $supplierMinPrice = min($offerCosts);
                $supplierMaxPrice = max($offerCosts);
            }
        }
    }

    // Fallback: If still null, calculate from snapshot variants or product cost
    if ($supplierMinPrice === null) {
        if (!empty($snapshot['variants']) && is_array($snapshot['variants'])) {
            $prices = array_filter(array_column($snapshot['variants'], 'price'), 'is_numeric');
            if (!empty($prices)) {
                $supplierMinPrice = min($prices);
                $supplierMaxPrice = max($prices);
            }
        } elseif (!empty($product->cost) && is_numeric($product->cost)) {
            $supplierMinPrice = (float) $product->cost;
            $supplierMaxPrice = (float) $product->cost;
        }
    }

    $baseShipping = $import->base_shipping_cost !== null ? (float) $import->base_shipping_cost : null;
    $isManualShipping = !empty($snapshot['is_manual_shipping']);
    $isShippingApiFailed = ($baseShipping === null) || $isManualShipping;
    // Manual shipping editing applies ONLY to the main product ($isVariant is false) AND only when API shipping fetch failed/unsynced
    $canEditManualShipping = ! $isVariant && $isShippingApiFailed;

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
            <div class="p-2.5 rounded-lg border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900/40 relative">
                <div class="flex items-center justify-between gap-1 mb-1">
                    <span class="text-gray-500 block">تكلفة الشحن:</span>

                    @if($canEditManualShipping)
                        <button 
                            type="button" 
                            onclick="toggleManualShippingEdit()"
                            class="inline-flex items-center justify-center p-1 rounded hover:bg-blue-50 dark:hover:bg-blue-950/60 text-blue-600 dark:text-blue-400 transition-all hover:scale-110 cursor-pointer"
                            title="تحرير وتحديد سعر الشحن يدوياً للمنتج وجميع متغيراته"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                <div id="shipping-cost-display-mode" class="flex items-center justify-between">
                    @if($baseShipping !== null)
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono text-sm" dir="ltr">
                            ${{ number_format($baseShipping, 2) }} {{ $import->shipping_currency ?? 'USD' }}
                        </span>
                        @if($isManualShipping)
                            <span class="text-[10px] bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 px-1.5 py-0.5 rounded font-semibold">
                                يدوي (كل المتغيرات)
                            </span>
                        @endif
                    @else
                        <span class="text-gray-400 font-medium">غير متزامن</span>
                    @endif
                </div>

                @if($canEditManualShipping)
                    <div id="shipping-cost-edit-mode" class="hidden mt-2 pt-2 border-t border-gray-150 dark:border-gray-800">
                        <div class="space-y-2">
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center px-2 text-gray-500 font-mono text-xs font-bold">$</span>
                                <input 
                                    type="number" 
                                    id="manual_shipping_cost_input"
                                    step="0.01" 
                                    min="0"
                                    value="{{ $baseShipping !== null ? number_format($baseShipping, 2, '.', '') : '' }}"
                                    placeholder="0.00"
                                    class="w-full ps-6 pe-2 py-1.5 text-xs font-mono font-bold border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button 
                                    type="button" 
                                    onclick="saveManualShippingCost({{ $import->id }}, this)"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-md shadow-xs cursor-pointer transition-all"
                                    style="background-color: #2563eb !important; color: #ffffff !important; border: 1px solid #1d4ed8 !important;"
                                    title="حفظ وينطبق على جميع المتغيرات"
                                >
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="stroke: #ffffff !important;">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span style="color: #ffffff !important; font-weight: bold;">حفظ السعر</span>
                                </button>
                                <button 
                                    type="button" 
                                    onclick="toggleManualShippingEdit()"
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-all"
                                    style="background-color: #f3f4f6 !important; color: #374151 !important; border: 1px solid #d1d5db !important;"
                                    title="إلغاء"
                                >
                                    <span style="color: #374151 !important;">إلغاء</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1.5 flex items-center gap-1">
                            <span>⚡</span>
                            <span>سينطبق السعر على المنتج وكافة متغيراته.</span>
                        </p>
                    </div>
                @endif
            </div>

            {{-- Carrier & Window --}}
            <div class="p-2.5 rounded-lg border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900/40">
                <span class="text-gray-500 block mb-1">شركة التوصيل والمدة:</span>
                <span class="font-bold text-gray-800 dark:text-gray-200 block truncate" title="{{ $import->shipping_company }}">
                    {{ $import->shipping_company ?? '—' }}
                </span>
                <span class="text-[10px] text-gray-500" dir="ltr">
                    {{ $import->shipping_min_days ?? '?' }}-{{ $import->shipping_max_days ?? '?' }} days
                    @if($import->shipping_tracking)
                        • Tracking ✅
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
                <span>{{ $isVariant ? 'سعر المتغير من المورد:' : 'سعر المنتج من المورد:' }}</span>
                <span class="font-mono font-semibold" dir="ltr">
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
                <span class="font-mono font-semibold text-emerald-600 dark:text-emerald-400" dir="ltr">
                    {{ $baseShipping !== null ? '+$' . number_format($baseShipping, 2) : '—' }}
                </span>
            </div>

            <div class="flex justify-between border-t border-blue-200 dark:border-blue-800 pt-1 font-bold text-gray-900 dark:text-white">
                <span>{{ $isVariant ? 'إجمالي تكلفة شراء المتغير عليك:' : 'إجمالي تكلفة الشراء عليك:' }}</span>
                <span class="font-mono text-blue-700 dark:text-blue-300" dir="ltr">
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
                <span>{{ $isVariant ? 'سعر بيع هذا المتغير في المتجر:' : 'سعر البيع الحالي في المتجر:' }}</span>
                <span class="font-mono font-bold text-gray-800 dark:text-gray-200" dir="ltr">
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

        window.toggleManualShippingEdit = function() {
            const displayMode = document.getElementById('shipping-cost-display-mode');
            const editMode = document.getElementById('shipping-cost-edit-mode');
            if (!displayMode || !editMode) return;

            if (editMode.classList.contains('hidden')) {
                editMode.classList.remove('hidden');
                displayMode.classList.add('hidden');
                const input = document.getElementById('manual_shipping_cost_input');
                if (input) input.focus();
            } else {
                editMode.classList.add('hidden');
                displayMode.classList.remove('hidden');
            }
        };

        window.saveManualShippingCost = function(importId, btnEl) {
            const input = document.getElementById('manual_shipping_cost_input');
            if (!input || !importId) return;

            const val = parseFloat(input.value);
            if (isNaN(val) || val < 0) {
                alert('الرجاء إدخال سعر شحن صحيح.');
                return;
            }

            const origHtml = btnEl ? btnEl.innerHTML : '';
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = '<span class="inline-block animate-spin text-xs">⏳</span> <span>جاري الحفظ...</span>';
            }

            const saveUrl = "{{ route('admin.audit-logs.products-import.update-manual-shipping', ['id' => ':id']) }}".replace(':id', importId);

            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    base_shipping_cost: val
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                if (res.status === 200 && res.body.success) {
                    if (window.emitter) {
                        window.emitter.emit('add-flash', { type: 'success', message: res.body.message || 'تم حفظ سعر الشحن اليدوي وتطبيقه على كل متغيرات المنتج.' });
                    }
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    alert(res.body.message || 'حدث خطأ أثناء حفظ سعر الشحن.');
                    if (btnEl) {
                        btnEl.disabled = false;
                        btnEl.innerHTML = origHtml;
                    }
                }
            })
            .catch(err => {
                console.error('Error saving manual shipping cost:', err);
                alert('حدث خطأ في الاتصال أثناء حفظ سعر الشحن.');
                if (btnEl) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = origHtml;
                }
            });
        };
    </script>
@endpushOnce
