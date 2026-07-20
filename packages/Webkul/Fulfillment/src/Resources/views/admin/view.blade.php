<x-admin::layouts>
    <x-slot:title>
        تفاصيل أمر الشراء #{{ $po->id }}
    </x-slot>

    <div class="flex flex-col gap-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.dropshipping.fulfillment.index') }}" class="text-sm text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 flex items-center gap-1">
                        <span class="icon-arrow-right transform rotate-180"></span>
                        العودة لقائمة التنفيذ
                    </a>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    أمر شراء #{{ $po->id }}
                    <span class="text-base font-normal text-gray-400 font-mono">({{ $po->internal_reference }})</span>
                    @php
                        $state = $po->state;
                        $label = trans("fulfillment::app.admin.states.{$state}");
                    @endphp
                    @switch($state)
                        @case('pending')
                            <span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('submitting')
                            <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('submitted')
                            <span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-400 border border-green-200 dark:border-green-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('shipped')
                            <span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-400 border border-purple-200 dark:border-purple-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('delivered')
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('needs_manual_review')
                            <span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('canceled')
                            <span class="px-2.5 py-1 rounded-full bg-gray-150 text-gray-700 dark:bg-gray-800 dark:text-gray-400 border border-gray-300 dark:border-gray-700 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @case('awaiting_payment_to_supplier')
                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 text-xs font-semibold">{{ $label }}</span>
                            @break
                        @default
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 text-xs font-semibold">{{ htmlspecialchars($state) }}</span>
                    @endswitch
                </h1>
            </div>

            {{-- Dynamic Actions --}}
            <div class="flex items-center gap-2">
                @php
                    $isPendingOrSubmitting = in_array($po->state, ['pending', 'submitting'], true);
                    $isSubmittedOrAwaiting = in_array($po->state, ['submitted', 'awaiting_payment_to_supplier', 'shipped'], true);
                    $isNeedsReview = ($po->state === 'needs_manual_review');
                    $isFinal = in_array($po->state, ['delivered', 'canceled'], true);
                @endphp

                @if (!$isPendingOrSubmitting && !$isFinal)
                    {{-- Refresh Status (enabled for submitted/awaiting/needs_manual_review) --}}
                    @if ($po->external_order_id)
                        <form action="{{ route('admin.dropshipping.fulfillment.refresh', $po->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-350 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-850 text-gray-700 dark:text-gray-300 rounded-md text-sm transition-all duration-300 flex items-center gap-1.5 shadow-sm font-semibold">
                                <span class="icon-settings animate-spin-slow"></span>
                                {{ trans('fulfillment::app.admin.datagrid.refresh') }}
                            </button>
                        </form>
                    @endif

                    {{-- Manual Retry (enabled only for needs_manual_review) --}}
                    @if ($isNeedsReview && config('fulfillment.retry_enabled', true))
                        <form action="{{ route('admin.dropshipping.fulfillment.retry', $po->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition-all duration-300 flex items-center gap-1.5 shadow-sm font-semibold">
                                <span class="icon-toast-done"></span>
                                {{ trans('fulfillment::app.admin.datagrid.retry') }}
                            </button>
                        </form>
                    @endif

                    {{-- Override State --}}
                    <button type="button" onclick="openModal('override-state-modal')" class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-350 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-850 text-gray-700 dark:text-gray-300 rounded-md text-sm transition-all duration-300 flex items-center gap-1.5 shadow-sm font-semibold">
                        تخطي الحالة
                    </button>

                    {{-- Edit Qty --}}
                    <button type="button" onclick="openModal('edit-qty-modal')" class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-350 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-850 text-gray-700 dark:text-gray-300 rounded-md text-sm transition-all duration-300 flex items-center gap-1.5 shadow-sm font-semibold">
                        تعديل الكميات
                    </button>

                    {{-- Cancel PO (enabled for submitted/awaiting/needs_manual_review) --}}
                    @if (config('fulfillment.manual_cancel_enabled', true))
                        <button type="button" onclick="openModal('cancel-po-modal')" class="px-4 py-2 bg-red-50 dark:bg-red-950/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-900/40 hover:bg-red-100 dark:hover:bg-red-950/35 rounded-md text-sm transition-all duration-300 flex items-center gap-1.5 shadow-sm font-semibold">
                            <span class="icon-cancel"></span>
                            {{ trans('fulfillment::app.admin.datagrid.cancel') }}
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Main Error Alert Box --}}
        @if ($po->last_error)
            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-l-rose-500 border border-rose-100 dark:border-rose-900/50 rounded-lg text-rose-800 dark:text-rose-400">
                <div class="flex items-start gap-3">
                    <span class="icon-cancel text-2xl mt-0.5"></span>
                    <div class="flex flex-col">
                        <span class="font-bold text-sm">تنبيه: محاولات إرسال الطلب تعطلت أو فشلت</span>
                        <p class="text-xs mt-1 font-mono leading-relaxed">{{ $po->last_error }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Detail Cards Layout --}}
        <div class="grid grid-cols-3 gap-6 max-xl:grid-cols-1">
            {{-- Column 1 & 2: Main Info --}}
            <div class="col-span-2 flex flex-col gap-6 max-xl:col-span-1">
                {{-- Purchase Order Items --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">أصناف أمر الشراء</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-right border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-800 text-gray-600 dark:text-gray-400 font-bold">
                                    <th class="p-3 whitespace-nowrap">المعرف</th>
                                    <th class="p-3 whitespace-nowrap">المنتج</th>
                                    <th class="p-3 whitespace-nowrap">معرف AliExpress</th>
                                    <th class="p-3 whitespace-nowrap">معرف SKU للمورد</th>
                                    <th class="p-3 text-center whitespace-nowrap">الكمية</th>
                                    <th class="p-3 text-center whitespace-nowrap">السعر الفردي</th>
                                    <th class="p-3 text-center whitespace-nowrap">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @php $grandTotal = 0; @endphp
                                @foreach($po->items as $item)
                                    @php 
                                        $itemTotal = ($item->qty * $item->supplier_unit_cost);
                                        $grandTotal += $itemTotal;
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                                        <td class="p-3 font-semibold text-gray-500">{{ $item->id }}</td>
                                        <td class="p-3">
                                            @if($item->orderItem)
                                                <div class="flex flex-col">
                                                    <span class="font-semibold text-gray-800 dark:text-white text-xs">{{ $item->orderItem->name }}</span>
                                                    <span class="text-[10px] text-gray-400 mt-0.5">رمز SKU: {{ $item->orderItem->sku }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 italic">غير متوفر</span>
                                            @endif
                                        </td>
                                        <td class="p-3 font-mono text-xs text-amber-700 dark:text-amber-500">
                                            @if($item->aliexpress_product_id)
                                                <a href="https://www.aliexpress.com/item/{{ $item->aliexpress_product_id }}.html" target="_blank" class="hover:underline">
                                                    {{ $item->aliexpress_product_id }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="p-3 font-mono text-xs">{{ $item->sku_id ?: 'N/A' }}</td>
                                        <td class="p-3 text-center font-bold text-gray-700 dark:text-gray-300">{{ $item->qty }}</td>
                                        <td class="p-3 text-center">{{ core()->formatPrice($item->supplier_unit_cost, $po->supplier_currency ?? 'USD') }}</td>
                                        <td class="p-3 text-center font-semibold">{{ core()->formatPrice($itemTotal, $po->supplier_currency ?? 'USD') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800 border-t-2 dark:border-gray-700 font-bold text-gray-800 dark:text-white">
                                    <td colspan="6" class="p-3 text-left">التكلفة الإجمالية:</td>
                                    <td class="p-3 text-center text-base text-amber-600 dark:text-amber-400">
                                        {{ core()->formatPrice($po->supplier_cost ?: $grandTotal, $po->supplier_currency ?? 'USD') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Fulfillment Attempts Timeline --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">سجل محاولات الإرسال والاتصال بالـ API</h2>
                    
                    @if($po->fulfillmentAttempts->isEmpty())
                        <div class="text-center py-6 text-gray-400 italic">لم يتم تسجيل أي محاولات إرسال حتى الآن.</div>
                    @else
                        <div class="relative border-r-2 border-gray-200 dark:border-gray-800 pr-6 mr-3 flex flex-col gap-6">
                            @foreach($po->fulfillmentAttempts as $attempt)
                                <div class="relative">
                                    {{-- Time Marker --}}
                                    <div class="absolute -right-[31px] top-1 w-4 h-4 rounded-full border-2 border-white dark:border-gray-900 {{ $attempt->result === 'success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></div>
                                    
                                    <div class="flex flex-col bg-gray-50 dark:bg-gray-800/40 p-4 rounded-lg border dark:border-gray-800/80">
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sm text-gray-800 dark:text-white">محاولة رقم #{{ $attempt->attempt_no }}</span>
                                            <span class="text-xs text-gray-400">{{ core()->formatDate($attempt->created_at, 'Y-m-d H:i:s') }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $attempt->result === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400' }}">
                                                {{ $attempt->result }}
                                            </span>
                                            @if($attempt->error_type)
                                                <span class="text-[10px] font-mono bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">
                                                    {{ $attempt->error_type }}
                                                </span>
                                            @endif
                                            @if($attempt->provider_code)
                                                <span class="text-[10px] font-mono bg-gray-200 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">
                                                    رمز الاستجابة: {{ $attempt->provider_code }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2 font-mono leading-relaxed bg-white dark:bg-gray-900 p-2.5 rounded border dark:border-gray-800">
                                            {{ $attempt->message }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Procurement & Allocation Status (Sprint 3) --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm flex flex-col gap-6" id="procurement-allocation-tracker">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white pb-3 border-b dark:border-gray-800">تعقب التوريد وتخصيص المخزون</h2>

                    {{-- 1. Procurement Session Step Wizard --}}
                    @if($procurementSessions->isNotEmpty())
                        @php $session = $procurementSessions->first(); @endphp
                        <div class="flex flex-col gap-3">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">حالة عملية التوريد (Procurement State Wizard)</span>
                            
                            {{-- Wizard steps --}}
                            <div class="grid grid-cols-5 items-center gap-2 pt-2 text-center text-xs">
                                @php
                                    $states = [
                                        'CREATED' => ['label' => 'إنشاء الجلسة', 'step' => 1],
                                        'VALIDATING' => ['label' => 'التحقق', 'step' => 2],
                                        'SUBMITTING' => ['label' => 'إرسال المورد', 'step' => 3],
                                        'PROCESSING' => ['label' => 'قيد المعالجة', 'step' => 4],
                                        'COMPLETED' => ['label' => 'مكتمل', 'step' => 5],
                                    ];
                                    
                                    $currentStepVal = 1;
                                    if ($session->state === 'VALIDATING' || $session->state === 'VALIDATED') {
                                        $currentStepVal = 2;
                                    } elseif ($session->state === 'READY_TO_SUBMIT' || $session->state === 'SUBMITTING') {
                                        $currentStepVal = 3;
                                    } elseif ($session->state === 'SUBMITTED' || $session->state === 'WAITING_PAYMENT' || $session->state === 'PROCESSING') {
                                        $currentStepVal = 4;
                                    } elseif ($session->state === 'COMPLETED' || $session->state === 'SHIPPED') {
                                        $currentStepVal = 5;
                                    } elseif ($session->state === 'FAILED') {
                                        $currentStepVal = -1; // failed
                                    }
                                @endphp

                                @foreach($states as $stateKey => $data)
                                    @php
                                        $isActive = $currentStepVal >= $data['step'];
                                        $isFailed = $session->state === 'FAILED' && $data['step'] === 2; // show red at validation step if failed
                                    @endphp
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold border-2 transition-all 
                                            {{ $isFailed ? 'bg-rose-50 border-rose-500 text-rose-600 dark:bg-rose-950/20' : ($isActive ? 'bg-amber-50 border-amber-500 text-amber-600 dark:bg-amber-950/20' : 'bg-gray-50 border-gray-200 text-gray-400 dark:bg-gray-800 dark:border-gray-700') }}">
                                            @if($session->state === 'FAILED' && $data['step'] === 2)
                                                ✖
                                            @else
                                                {{ $data['step'] }}
                                            @endif
                                        </div>
                                        <span class="font-semibold {{ $isActive ? 'text-gray-850 dark:text-gray-200' : 'text-gray-400' }}">{{ $data['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- Extra Session Info --}}
                            <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-lg border dark:border-gray-800 text-xs flex flex-col gap-1.5 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">حالة جلسة التوريد الحالية:</span>
                                    <span class="font-bold font-mono {{ $session->state === 'FAILED' ? 'text-rose-600' : 'text-amber-600' }}">{{ $session->state }}</span>
                                </div>
                                @if($session->error_message)
                                    <div class="text-rose-600 dark:text-rose-400 font-mono mt-1 pt-1 border-t border-rose-100 dark:border-rose-900/50">
                                        <strong>رسالة الخطأ:</strong> {{ $session->error_message }}
                                    </div>
                                @endif
                                <div class="flex justify-between mt-1 text-[10px] text-gray-450">
                                    <span>الرقم المرجعي للجلسة: {{ $session->id }}</span>
                                    <span>تحديث: {{ $session->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-gray-400 italic text-xs font-sans">لا توجد جلسات توريد (Procurement Sessions) مسجلة لهذا الطلب.</div>
                    @endif

                    {{-- 2. Allocations Breakdown --}}
                    <div class="flex flex-col gap-3">
                        <span class="text-sm font-semibold text-gray-600 dark:text-gray-400">حالة حجز وتعيين المخزون (Inventory Allocations)</span>
                        @if($allocations->isEmpty())
                            <div class="text-center py-4 text-gray-400 italic text-xs font-sans">لا توجد عمليات حجز مخزون (Allocations) مسجلة لهذا الطلب.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-right border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-850 text-gray-650 dark:text-gray-400 font-bold">
                                            <th class="p-3">المنتج المحلي</th>
                                            <th class="p-3">النوع</th>
                                            <th class="p-3 text-center">المحجوز</th>
                                            <th class="p-3 text-center">المنفّذ</th>
                                            <th class="p-3 text-center">الملغى</th>
                                            <th class="p-3 text-center">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                                        @foreach($allocations as $alloc)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                                                <td class="p-3 font-semibold">
                                                    {{ $alloc->product ? $alloc->product->name : 'N/A' }}
                                                    @if($alloc->variantProduct)
                                                        <span class="block text-[10px] text-gray-450 mt-0.5">{{ $alloc->variantProduct->name }}</span>
                                                    @endif
                                                </td>
                                                <td class="p-3 capitalize font-sans text-gray-500">{{ $alloc->allocation_type }}</td>
                                                <td class="p-3 text-center font-bold font-mono">{{ $alloc->reserved_qty }}</td>
                                                <td class="p-3 text-center font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ $alloc->fulfilled_qty }}</td>
                                                <td class="p-3 text-center font-bold font-mono text-rose-600 dark:text-rose-450">{{ $alloc->canceled_qty }}</td>
                                                <td class="p-3 text-center">
                                                    @if($alloc->state === 'fulfilled')
                                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-250 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-sans font-semibold">مكتمل</span>
                                                    @elseif($alloc->state === 'reserved')
                                                        <span class="inline-flex items-center gap-1 rounded bg-blue-50 dark:bg-blue-950/20 border border-blue-250 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-450 font-sans font-semibold">محجوز</span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 rounded bg-red-50 dark:bg-red-950/20 border border-red-250 px-2 py-0.5 text-[10px] font-bold text-red-700 dark:text-red-400 font-sans font-semibold">{{ $alloc->state }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            {{-- Logs for this allocation --}}
                                            @if($alloc->logs->isNotEmpty())
                                                <tr class="bg-gray-50/20 dark:bg-gray-800/10">
                                                    <td colspan="6" class="p-3">
                                                        <div class="flex flex-col gap-1 pr-4 text-[10px] text-gray-500 font-mono text-right">
                                                            @foreach($alloc->logs as $log)
                                                                <div>
                                                                    [{{ $log->created_at->format('Y-m-d H:i:s') }}] {{ $log->message }} (حالة: {{ $log->state_from }} ➔ {{ $log->state_to }})
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Provider Events --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">أحداث ومستندات المزود (Raw Provider Events)</h2>
                    
                    @if($po->events->isEmpty())
                        <div class="text-center py-6 text-gray-400 italic">لم تتوفر أي استجابات خام من الموفر بعد.</div>
                    @else
                        <div class="flex flex-col gap-4">
                            @foreach($po->events as $event)
                                <div class="border dark:border-gray-800 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800/60 p-4 flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition" onclick="toggleDetails('evt-{{ $event->id }}')">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold font-mono bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400 px-2 py-0.5 rounded">
                                                {{ $event->source_type }}
                                            </span>
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                حالة المورد: {{ $event->external_state }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-gray-400">{{ core()->formatDate($event->created_at, 'Y-m-d H:i:s') }}</span>
                                            <span class="icon-arrow-down transform transition duration-200 text-gray-400" id="evt-icon-evt-{{ $event->id }}"></span>
                                        </div>
                                    </div>
                                    
                                    <div id="evt-{{ $event->id }}" class="hidden p-4 border-t dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/20">
                                        <pre class="text-xs font-mono text-gray-700 dark:text-gray-300 overflow-x-auto p-3 bg-white dark:bg-gray-900 rounded border dark:border-gray-800 leading-relaxed">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Approval Requests Workflow --}}
                @if (config('fulfillment.approval_workflow.enabled', false))
                    <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">مسار الموافقات وطلبات التعديل</h2>
                        
                        @if($po->approvalRequests->isEmpty())
                            <div class="text-center py-6 text-gray-400 italic">لا توجد أي طلبات موافقة مسجلة.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-right border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-800 text-gray-600 dark:text-gray-400 font-bold">
                                            <th class="p-3">المعرف</th>
                                            <th class="p-3">الإجراء المطلوب</th>
                                            <th class="p-3">بواسطة</th>
                                            <th class="p-3">السبب</th>
                                            <th class="p-3 text-center">الحالة</th>
                                            <th class="p-3">المشرف</th>
                                            <th class="p-3 text-center">العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                        @foreach($po->approvalRequests as $req)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                                                <td class="p-3 font-semibold text-gray-500">{{ $req->id }}</td>
                                                <td class="p-3 capitalize font-mono text-xs">{{ str_replace('_', ' ', $req->action) }}</td>
                                                <td class="p-3">{{ $req->requestedBy?->name ?: 'N/A' }}</td>
                                                <td class="p-3 text-xs">{{ $req->reason }}</td>
                                                <td class="p-3 text-center">
                                                    @switch($req->status)
                                                        @case('pending')
                                                            <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 border border-yellow-200 text-xs">Pending</span>
                                                            @break
                                                        @case('approved')
                                                            <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 border border-green-200 text-xs">Approved</span>
                                                            @break
                                                        @case('executed')
                                                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs">Executed</span>
                                                            @break
                                                        @case('rejected')
                                                            <span class="px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-200 text-xs">Rejected</span>
                                                            @break
                                                    @endswitch
                                                </td>
                                                <td class="p-3 text-xs">{{ $req->approvedBy?->name ?: '-' }}</td>
                                                <td class="p-3 text-center flex items-center justify-center gap-1">
                                                    @if($req->status === 'pending' && bouncer()->hasPermission('dropshipping.fulfillment.approve'))
                                                        <form action="{{ route('admin.dropshipping.fulfillment.approve', $req->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700">اعتماد</button>
                                                        </form>
                                                        <form action="{{ route('admin.dropshipping.fulfillment.reject', $req->id) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">رفض</button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400 italic text-xs">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Column 3: Sidebar Details --}}
            <div class="col-span-1 flex flex-col gap-6">
                {{-- General Info Card --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white border-b dark:border-gray-800 pb-3 mb-4">معلومات أمر الشراء</h2>
                    
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">حالة أمر الشراء:</span>
                            @php
                                $state = $po->state;
                                $label = trans("fulfillment::app.admin.states.{$state}");
                            @endphp
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $label }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">رمز الموفر:</span>
                            <span class="font-semibold text-gray-800 dark:text-white capitalize">{{ $po->provider }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">متجر المورد:</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $po->supplier_signature ?: 'N/A' }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">رقم الطلب الخارجي:</span>
                            @if($po->external_order_id)
                                <a href="https://trade.aliexpress.com/order_detail.htm?orderId={{ $po->external_order_id }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-mono font-bold truncate">
                                    {{ $po->external_order_id }}
                                </a>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic">غير متوفر</span>
                            @endif
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">رقم التتبع:</span>
                            <span class="font-semibold text-gray-800 dark:text-white font-mono">{{ $po->tracking_number ?: 'N/A' }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">شركة الشحن:</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $po->tracking_company ?: 'N/A' }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">عدد المحاولات الآلية:</span>
                            <span class="font-bold text-gray-800 dark:text-white font-mono">{{ $po->attempts }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">تاريخ الإنشاء:</span>
                            <span class="text-gray-800 dark:text-gray-200 font-mono">{{ core()->formatDate($po->created_at, 'Y-m-d H:i') }}</span>
                        </div>

                        <div class="flex flex-col gap-1 col-span-2">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">تاريخ التقديم للمورد:</span>
                            <span class="text-gray-800 dark:text-gray-200 font-mono">{{ $po->submitted_at ? core()->formatDate($po->submitted_at, 'Y-m-d H:i') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Connected Customer Order Card --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white border-b dark:border-gray-800 pb-3 mb-4">تفاصيل طلب العميل</h2>
                    
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">رقم الطلب الداخلي:</span>
                            <a href="{{ route('admin.sales.orders.view', $po->order_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold">
                                #{{ $po->order->increment_id }}
                            </a>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">حالة طلب العميل:</span>
                            <span class="font-semibold capitalize text-amber-600 dark:text-amber-400">{{ $po->order->status }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">اسم العميل:</span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $po->order->customer_full_name }}</span>
                        </div>

                        <div class="flex flex-col gap-1">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">بريد العميل:</span>
                            <span class="font-semibold text-gray-800 dark:text-white truncate">{{ $po->order->customer_email }}</span>
                        </div>

                        <div class="flex flex-col gap-1 col-span-2 mt-2">
                            <span class="text-gray-450 dark:text-gray-550 border-b pb-1 dark:border-gray-805 font-semibold">عنوان الشحن:</span>
                            @if($po->order->shipping_address)
                                <div class="bg-gray-50 dark:bg-gray-800/40 p-3 rounded border dark:border-gray-800 mt-1 leading-relaxed text-xs">
                                    <p class="font-semibold text-gray-800 dark:text-white">{{ $po->order->shipping_address->first_name }} {{ $po->order->shipping_address->last_name }}</p>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $po->order->shipping_address->address }}</p>
                                    <p class="text-gray-600 dark:text-gray-400">{{ $po->order->shipping_address->city }}, {{ $po->order->shipping_address->state }} {{ $po->order->shipping_address->postcode }}</p>
                                    <p class="text-gray-600 dark:text-gray-400 font-bold mt-1">{{ core()->country_name($po->order->shipping_address->country) }}</p>
                                    <p class="text-gray-600 dark:text-gray-400 font-mono mt-1">هاتف: {{ $po->order->shipping_address->phone }}</p>
                                </div>
                            @else
                                <span class="text-gray-400 dark:text-gray-500 italic">لا يوجد عنوان شحن مسجل</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Operator Audit Logs Trail --}}
                <div class="p-6 bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-sm">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white border-b dark:border-gray-800 pb-3 mb-4">سجل العمليات البشرية (Audit Trail)</h2>
                    
                    @if($po->auditLogs->isEmpty())
                        <div class="text-center py-4 text-gray-400 italic text-xs">لم يتم تسجيل أي عمليات يدوية بعد.</div>
                    @else
                        <div class="flex flex-col gap-4 max-h-[350px] overflow-y-auto pr-1">
                            @foreach($po->auditLogs as $log)
                                <div class="bg-gray-50 dark:bg-gray-800/40 p-3 rounded border dark:border-gray-800/80 flex flex-col gap-1.5 text-[11px] leading-relaxed">
                                    <div class="flex items-center justify-between border-b dark:border-gray-800 pb-1.5">
                                        <span class="font-bold text-gray-800 dark:text-white capitalize">إجراء: {{ $log->action }}</span>
                                        <span class="text-[10px] text-gray-400">{{ core()->formatDate($log->created_at, 'Y-m-d H:i:s') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">بواسطة:</span>
                                        <span class="font-semibold">{{ $log->user?->name ?: 'System' }}</span>
                                        <span class="text-gray-400 font-mono">({{ $log->ip_address }})</span>
                                    </div>
                                    @if ($log->reason)
                                        <p class="bg-white dark:bg-gray-900 p-2 rounded border dark:border-gray-800 mt-1 italic text-gray-600 dark:text-gray-400 text-xs">
                                            "{{ $log->reason }}"
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ================== MODALS ================== --}}

    {{-- 1. Cancel Purchase Order Modal --}}
    <div id="cancel-po-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 flex flex-col gap-4">
            <div class="flex items-center justify-between border-b dark:border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">تأكيد إلغاء أمر الشراء</h3>
                <span class="icon-cancel text-xl cursor-pointer text-gray-400 hover:text-gray-600" onclick="closeModal('cancel-po-modal')"></span>
            </div>
            
            <form action="{{ route('admin.dropshipping.fulfillment.cancel', $po->id) }}" method="POST" onsubmit="return validateReason('cancel-reason')">
                @csrf
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans('fulfillment::app.admin.actions.confirm-cancel') }}
                </p>

                <div class="flex flex-col gap-2 mt-4">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">سبب الإلغاء (على الأقل 10 أحرف) <span class="text-red-500">*</span></label>
                    <textarea 
                        name="reason" 
                        id="cancel-reason" 
                        rows="3" 
                        placeholder="{{ trans('fulfillment::app.admin.actions.reason-placeholder') }}" 
                        class="w-full border border-gray-300 dark:border-gray-700 rounded-md p-3 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    ></textarea>
                    <span id="cancel-reason-error" class="hidden text-xs text-rose-500 font-semibold"></span>
                </div>

                <div class="flex justify-end gap-3 mt-6 border-t dark:border-gray-800 pt-4">
                    <button type="button" onclick="closeModal('cancel-po-modal')" class="secondary-button py-2 px-4 rounded-md">إلغاء</button>
                    <button type="submit" class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm transition">تأكيد الإلغاء</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 2. Override State Modal --}}
    <div id="override-state-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 flex flex-col gap-4">
            <div class="flex items-center justify-between border-b dark:border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">تعديل وتخطي الحالة يدوياً</h3>
                <span class="icon-cancel text-xl cursor-pointer text-gray-400 hover:text-gray-600" onclick="closeModal('override-state-modal')"></span>
            </div>
            
            <form action="{{ route('admin.dropshipping.fulfillment.override', $po->id) }}" method="POST" onsubmit="return validateReason('override-reason')">
                @csrf
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans('fulfillment::app.admin.actions.confirm-override') }}
                </p>

                <div class="flex flex-col gap-2 mt-4">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">اختر الحالة الجديدة <span class="text-red-500">*</span></label>
                    <select name="state" class="w-full border border-gray-300 dark:border-gray-700 rounded-md p-3 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none">
                        <option value="pending" {{ $po->state === 'pending' ? 'selected' : '' }}>Pending (معلق)</option>
                        <option value="submitting" {{ $po->state === 'submitting' ? 'selected' : '' }}>Submitting (جاري الإرسال)</option>
                        <option value="submitted" {{ $po->state === 'submitted' ? 'selected' : '' }}>Placed (تم وضعه)</option>
                        <option value="shipped" {{ $po->state === 'shipped' ? 'selected' : '' }}>Shipped (تم الشحن)</option>
                        <option value="delivered" {{ $po->state === 'delivered' ? 'selected' : '' }}>Delivered (تم التوصيل)</option>
                        <option value="needs_manual_review" {{ $po->state === 'needs_manual_review' ? 'selected' : '' }}>Needs Review (بحاجة لمراجعة يدوية)</option>
                        <option value="canceled" {{ $po->state === 'canceled' ? 'selected' : '' }}>Canceled (ملغي)</option>
                        <option value="awaiting_payment_to_supplier" {{ $po->state === 'awaiting_payment_to_supplier' ? 'selected' : '' }}>Awaiting Payment (بانتظار الدفع)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-2 mt-4">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">سبب تعديل الحالة (على الأقل 10 أحرف) <span class="text-red-500">*</span></label>
                    <textarea 
                        name="reason" 
                        id="override-reason" 
                        rows="3" 
                        placeholder="{{ trans('fulfillment::app.admin.actions.reason-placeholder') }}" 
                        class="w-full border border-gray-300 dark:border-gray-700 rounded-md p-3 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    ></textarea>
                    <span id="override-reason-error" class="hidden text-xs text-rose-500 font-semibold"></span>
                </div>

                <div class="flex justify-end gap-3 mt-6 border-t dark:border-gray-800 pt-4">
                    <button type="button" onclick="closeModal('override-state-modal')" class="secondary-button py-2 px-4 rounded-md">إلغاء</button>
                    <button type="submit" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. Edit Item Quantities Modal --}}
    <div id="edit-qty-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 flex flex-col gap-4">
            <div class="flex items-center justify-between border-b dark:border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">تعديل كميات أمر الشراء</h3>
                <span class="icon-cancel text-xl cursor-pointer text-gray-400 hover:text-gray-600" onclick="closeModal('edit-qty-modal')"></span>
            </div>
            
            <form action="{{ route('admin.dropshipping.fulfillment.edit', $po->id) }}" method="POST" onsubmit="return validateReason('edit-reason')">
                @csrf
                <div class="flex flex-col gap-4 mt-2">
                    @foreach($po->items as $item)
                        <div class="flex items-center justify-between gap-4 border-b dark:border-gray-800 pb-3">
                            <div class="flex flex-col text-xs max-w-[250px]">
                                <span class="font-semibold truncate">{{ $item->orderItem ? $item->orderItem->name : 'Item #' . $item->id }}</span>
                                <span class="text-[10px] text-gray-400 mt-0.5">رمز SKU للمورد: {{ $item->sku_id ?: 'N/A' }}</span>
                            </div>
                            <input 
                                type="number" 
                                name="qty[{{ $item->id }}]" 
                                value="{{ $item->qty }}" 
                                min="1" 
                                class="w-20 border border-gray-300 dark:border-gray-700 rounded-md p-2 text-center text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:outline-none"
                            />
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col gap-2 mt-4">
                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">سبب تعديل الكميات (على الأقل 10 أحرف) <span class="text-red-500">*</span></label>
                    <textarea 
                        name="reason" 
                        id="edit-reason" 
                        rows="3" 
                        placeholder="{{ trans('fulfillment::app.admin.actions.reason-placeholder') }}" 
                        class="w-full border border-gray-300 dark:border-gray-700 rounded-md p-3 text-sm bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    ></textarea>
                    <span id="edit-reason-error" class="hidden text-xs text-rose-500 font-semibold"></span>
                </div>

                <div class="flex justify-end gap-3 mt-6 border-t dark:border-gray-800 pt-4">
                    <button type="button" onclick="closeModal('edit-qty-modal')" class="secondary-button py-2 px-4 rounded-md">إلغاء</button>
                    <button type="submit" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm transition">حفظ وتعديل</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Script helper to handle modal visibility toggles --}}
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        function toggleDetails(id) {
            const container = document.getElementById(id);
            const icon = document.getElementById('evt-icon-' + id);
            
            if (container) {
                if (container.classList.contains('hidden')) {
                    container.classList.remove('hidden');
                    if (icon) icon.classList.add('rotate-180');
                } else {
                    container.classList.add('hidden');
                    if (icon) icon.classList.remove('rotate-180');
                }
            }
        }

        function validateReason(textareaId) {
            const textarea = document.getElementById(textareaId);
            const errorSpan = document.getElementById(textareaId + '-error');
            
            if (!textarea || !textarea.value || textarea.value.trim().length < 10) {
                if (errorSpan) {
                    errorSpan.textContent = '{{ trans("fulfillment::app.admin.actions.reason-required") }}';
                    errorSpan.classList.remove('hidden');
                }
                if (textarea) textarea.classList.add('border-rose-500');
                return false;
            }
            
            if (errorSpan) errorSpan.classList.add('hidden');
            if (textarea) textarea.classList.remove('border-rose-500');
            return true;
        }
    </script>
</x-admin::layouts>
