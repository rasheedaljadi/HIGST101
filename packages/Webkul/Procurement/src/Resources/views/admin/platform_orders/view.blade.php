<x-admin::layouts>
    <x-slot:title>
        {{ trans('procurement::app.platform_orders.title') ?: 'أوامر المنصة' }} #{{ $order->external_order_id ?: $order->id }}
    </x-slot>

    @php
        $normalizedStatus = $order->normalized_status ?? 'wait_buyer_pay';

        // 1. ترجمة الحالات المعيارية
        $statusLabels = [
            'wait_buyer_pay'     => 'قيد انتظار الدفع',
            'processing'         => 'جاري الإجراء (قيد التجهيز)',
            'shipped'            => 'تم الإجراء (تم الشحن)',
            'completed'          => 'تم الاكتمال',
            'cancelled'          => 'ملغي',
            'submission_failed'  => 'فشل الإرسال',
        ];
        $currentStatusLabel = $statusLabels[$normalizedStatus] ?? (trans("procurement::app.platform_orders.statuses.{$normalizedStatus}") ?: $normalizedStatus);

        $statusColors = [
            'wait_buyer_pay'     => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-700',
            'processing'         => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700',
            'shipped'            => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-700',
            'completed'          => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-700',
            'cancelled'          => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-700',
            'submission_failed'  => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-300 dark:border-red-700',
        ];
        $badgeClass = $statusColors[$normalizedStatus] ?? 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';

        // 2. ترجمة حالات علي إكسبرس الأصلية (Raw Statuses)
        $rawStatusMap = [
            'PLACE_ORDER_SUCCESS'      => 'تم إنشاء الطلب بنجاح',
            'WAIT_BUYER_PAY'          => 'بانتظار سداد المشتري',
            'PAYMENT_CONFIRMED'        => 'تم تأكيد الدفع',
            'WAIT_SELLER_SEND_GOODS'   => 'بانتظار شحن المورد',
            'SELLER_SEND_PART_GOODS'   => 'تم الشحن الجزئي',
            'SELLER_SEND_GOODS'        => 'تم تسليم الشحنة للناقل',
            'WAIT_BUYER_ACCEPT_GOODS'  => 'تم الشحن (بانتظار استلام المشتري)',
            'FINISH'                   => 'منتهي في المنصة',
            'COMPLETED'                => 'مكتمل',
            'IN_CANCEL'                => 'قيد طلب الإلغاء',
            'CANCELLED'                => 'ملغي',
            'CLOSED'                   => 'مغلق',
            'RISK_CONTROL'             => 'مراجعة الأمان',
            'UNKNOWN'                  => 'غير محدد',
        ];
        $rawStatusText = $rawStatusMap[$order->raw_status] ?? $order->raw_status;

        // 3. ترجمة الحالات اللوجستية (Logistics Status)
        $logisticsStatusMap = [
            'NO_LOGISTICS'            => 'لا توجد بوليصة شحن',
            'WAIT_SELLER_SEND_GOODS'  => 'بانتظار تسليم البائع للشحنة',
            'SELLER_SEND_PART_GOODS'  => 'شحن جزئي',
            'SELLER_SEND_GOODS'       => 'تم تسليم الشحنة لشركة الشحن',
            'BUYER_ACCEPT_GOODS'      => 'تم استلام الشحنة',
        ];
        $rawLogisticsStatus = $liveData['logistics_status'] ?? null;
        $logisticsStatusText = $logisticsStatusMap[$rawLogisticsStatus] ?? $rawLogisticsStatus;

        // 4. ترجمة شركات وخدمات الشحن
        $carrierMap = [
            'CAINIAO_FULFILLMENT_STD' => 'شحن قينياو القياسي (Cainiao Fulfillment Standard)',
            'CAINIAO_EXPEDITED'       => 'شحن قينياو السريع (Cainiao Expedited)',
            'CAINIAO_SUPER_ECONOMY'   => 'شحن قينياو الاقتصادي (Cainiao Super Economy)',
            'ALIEXPRESS_STANDARD'     => 'شحن علي إكسبرس القياسي',
            'AE_PREMIUM'              => 'شحن علي إكسبرس الممتاز',
            'DHL'                     => 'دي إتش إل (DHL)',
            'FEDEX'                   => 'فيديكس (FedEx)',
            'ARAMEX'                  => 'أرامكس (Aramex)',
        ];
        $rawCarrier = $order->carrier_name ?: ($logisticsList[0]['logistics_service'] ?? $logisticsList[0]['logistics_service_name'] ?? null);
        $carrierText = $carrierMap[$rawCarrier] ?? $rawCarrier;

        // 5. ترجمة أسباب الإلغاء
        $endReasonMap = [
            'PAYMENT_TIMEOUT_BUYER'           => 'انتهاء مهلة السداد المحددة للمشتري',
            'PAYMENT_TIMEOUT'                 => 'انتهاء مهلة السداد',
            'No payment made from the buyer'  => 'لم يتم سداد قيمة الطلب من قبل المشتري خلال المهلة المحددة',
            'BUYER_CANCEL_ORDER'              => 'إلغاء الطلب من قبل المشتري',
            'BUYER_CANCEL'                    => 'إلغاء المشتري',
            'SELLER_CANCEL'                   => 'إلغاء الطلب من قبل المورد / البائع',
            'RISK_CONTROL'                    => 'إلغاء أمني لحماية الحساب من قبل علي إكسبرس',
            'OUT_OF_STOCK'                    => 'نفاد المخزون لدى المورد',
        ];

        $trackingNum = $order->tracking_number ?: ($logisticsList[0]['logistics_no'] ?? $logisticsList[0]['tracking_no'] ?? null);
        $orderAmount = $liveData['order_amount']['amount'] ?? $order->supplierPurchaseOrder?->actual_total ?? $order->supplierPurchaseOrder?->expected_total ?? '0.00';
        $currency = $liveData['order_amount']['currency_code'] ?? $order->currency_code ?? 'USD';

        $firstItem = $childItems[0] ?? [];
        $rawEndReason = $firstItem['end_reason'] ?? ($liveData['end_reason'] ?? null);
        $rawEndReasonDesc = $firstItem['end_reason_desc'] ?? ($liveData['end_reason_desc'] ?? ($order->failure_message ?? null));
        $endReasonText = $endReasonMap[$rawEndReasonDesc] ?? ($endReasonMap[$rawEndReason] ?? ($rawEndReasonDesc ?: $rawEndReason));

        $paidTime = $liveData['order_paidtime_string'] ?? null;
        if ($paidTime && str_contains($paidTime, 'T')) {
            try {
                $paidTime = \Carbon\Carbon::parse(explode('[', $paidTime)[0])->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // keep formatted or raw
            }
        }
        $createdTime = $liveData['gmt_create'] ?? $order->created_at ?? null;
        if ($createdTime) {
            try {
                $createdTime = \Carbon\Carbon::parse($createdTime)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        }
    @endphp

    <div class="flex flex-col gap-6 max-w-7xl mx-auto pb-12">
        {{-- Breadcrumb & Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4 bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <a href="{{ route('admin.procurement.platform_orders.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        {{ trans('procurement::app.platform_orders.title') ?: 'أوامر منصة علي إكسبرس' }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-white font-semibold font-mono">#{{ $order->external_order_id ?: $order->id }}</span>
                </div>
                <div class="flex items-center gap-3 flex-wrap mt-1">
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                        أمر شراء علي إكسبرس: <span class="font-mono text-blue-600 dark:text-blue-400">#{{ $order->external_order_id }}</span>
                    </h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $badgeClass }}">
                        {{ $currentStatusLabel }}
                    </span>
                    @if ($rawStatusText)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700" title="{{ $order->raw_status }}">
                            الحالة بالمنصة: {{ $rawStatusText }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                {{-- Live Sync Button --}}
                <form action="{{ route('admin.procurement.platform_orders.sync', $order->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150">
                        <span class="icon-refresh text-lg"></span>
                        <span>مزامنة حية من علي إكسبرس</span>
                    </button>
                </form>

                @if ($order->supplierPurchaseOrder)
                    <a href="{{ route('admin.procurement.supplier_orders.view', $order->supplierPurchaseOrder->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm font-semibold rounded-xl border border-gray-300 dark:border-gray-700 transition duration-150">
                        <span class="icon-view text-lg"></span>
                        <span>أمر الشراء بالمخزون (SPO)</span>
                    </a>
                @endif

                @if ($normalizedStatus === 'wait_buyer_pay')
                    <form action="{{ route('admin.procurement.platform_orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء أمر الشراء في علي إكسبرس؟');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-300 text-sm font-semibold rounded-xl border border-rose-200 dark:border-rose-800 transition duration-150">
                            <span class="icon-cancel text-lg"></span>
                            <span>إلغاء الطلب</span>
                        </button>
                    </form>
                @endif

                @if ($normalizedStatus === 'cancelled')
                    <form action="{{ route('admin.procurement.platform_orders.reorder', $order->id) }}" method="POST" onsubmit="return confirm('هل تريد إعادة إنشاء أمر الشراء وإرساله مجدداً لعلي إكسبرس؟');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-sm transition duration-150">
                            <span class="icon-cart text-lg"></span>
                            <span>إعادة الطلب</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.procurement.platform_orders.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl border border-gray-300 dark:border-gray-700 transition duration-150">
                    <span>رجوع للقائمة</span>
                </a>
            </div>
        </div>

        {{-- Top Summary Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Stat 1: Total Cost --}}
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/40 dark:from-emerald-950/30 dark:to-emerald-900/10 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">التكلفة الإجمالية للطلب</span>
                <div class="text-3xl font-black text-emerald-900 dark:text-emerald-200 mt-2 font-mono">
                    ${{ number_format((float) $orderAmount, 2) }}
                    <span class="text-sm font-normal text-emerald-600 dark:text-emerald-400">{{ $currency }}</span>
                </div>
                <div class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                    {{ $order->supplierPurchaseOrder ? 'مرتبط بأمر شراء #' . $order->supplierPurchaseOrder->purchase_order_number : 'طلب شراء مورد مباشر' }}
                </div>
            </div>

            {{-- Stat 2: Logistics & Tracking --}}
            <div class="bg-gradient-to-br from-blue-50 to-blue-100/40 dark:from-blue-950/30 dark:to-blue-900/10 border border-blue-200 dark:border-blue-800/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">رقم بوليصة التتبع</span>
                <div class="text-xl font-black text-blue-900 dark:text-blue-200 mt-2 font-mono truncate">
                    @if ($trackingNum)
                        {{ $trackingNum }}
                    @elseif ($normalizedStatus === 'cancelled')
                        <span class="text-rose-600 dark:text-rose-400 text-base font-sans font-bold">لا يوجد (طلب ملغي)</span>
                    @elseif ($normalizedStatus === 'wait_buyer_pay')
                        <span class="text-amber-600 dark:text-amber-400 text-base font-sans font-bold">بانتظار السداد</span>
                    @else
                        <span class="text-gray-500 dark:text-gray-400 text-base font-sans font-bold">قيد التجهيز</span>
                    @endif
                </div>
                <div class="text-xs text-blue-700 dark:text-blue-400 mt-1 truncate">
                    {{ $carrierText ?: ($normalizedStatus === 'cancelled' ? 'لم يتم الشحن' : 'شركة الشحن لم تحدد بعد') }}
                </div>
            </div>

            {{-- Stat 3: Order Status --}}
            <div class="bg-gradient-to-br from-purple-50 to-purple-100/40 dark:from-purple-950/30 dark:to-purple-900/10 border border-purple-200 dark:border-purple-800/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">الحالة في المنصة</span>
                <div class="text-xl font-black text-purple-900 dark:text-purple-200 mt-2">
                    {{ $currentStatusLabel }}
                </div>
                <div class="text-xs text-purple-700 dark:text-purple-400 mt-1">
                    {{ $logisticsStatusText ? "لوجستياً: {$logisticsStatusText}" : ($rawStatusText ?: 'نشط') }}
                </div>
            </div>

            {{-- Stat 4: Store & Sync --}}
            <div class="bg-gradient-to-br from-gray-50 to-gray-100/40 dark:from-gray-900 dark:to-gray-800/60 border border-gray-200 dark:border-gray-700/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">متجر المورد / آخر مزامنة</span>
                <div class="text-base font-bold text-gray-900 dark:text-white mt-2 truncate" title="{{ $storeInfo['store_name'] ?? $order->supplier_store_id ?? 'متجر علي إكسبرس' }}">
                    {{ $storeInfo['store_name'] ?? $order->supplier_store_id ?? 'متجر علي إكسبرس' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $order->last_synced_at ? \Carbon\Carbon::parse($order->last_synced_at)->diffForHumans() : 'منذ لحظات' }}
                </div>
            </div>
        </div>

        @if ($liveError)
            <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700 rounded-2xl p-5 shadow-sm flex items-start gap-4">
                <span class="text-2xl text-amber-600">⚠️</span>
                <div>
                    <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">تنبيه اتصال مع علي إكسبرس</h3>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                        تعذر جلب تفاصيل الاستجابة الحية مباشرة من الـ API: <span class="font-mono">{{ $liveError }}</span>. يتم عرض البيانات المسجلة مسبقاً في قاعدة بيانات النظام.
                    </p>
                </div>
            </div>
        @endif

        {{-- SECTION 1: Product & Items Data --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/40">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">📦</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">بيانات المنتج والأصناف المشتراة</h2>
                </div>
                <span class="text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full">
                    {{ count($childItems) ?: ($order->supplierPurchaseOrder?->items ? count($order->supplierPurchaseOrder->items) : 1) }} صنف
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm ltr:text-left rtl:text-right text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800/80 text-xs font-bold uppercase text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="p-4 ltr:text-left rtl:text-right">المنتج / الصنف</th>
                            <th class="p-4 text-center">معرف المنتج في علي إكسبرس</th>
                            <th class="p-4 text-center">معرف المتغير (SKU ID)</th>
                            <th class="p-4 text-center">الكمية</th>
                            <th class="p-4 ltr:text-right rtl:text-left">سعر الوحدة</th>
                            <th class="p-4 ltr:text-right rtl:text-left">إجمالي الصنف</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @if (! empty($childItems))
                            @foreach ($childItems as $item)
                                @php
                                    $pId = $item['product_id'] ?? null;
                                    $skuId = $item['sku_id'] ?? null;
                                    $pName = $item['product_name'] ?? 'منتج علي إكسبرس';
                                    $pQty = $item['product_count'] ?? 1;
                                    $unitPrice = $item['product_price']['amount'] ?? ($item['sale_fee']['amount'] ?? '0.00');
                                    $actualFee = $item['actual_fee']['amount'] ?? ($item['sale_fee']['amount'] ?? '0.00');
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900 dark:text-white max-w-md leading-snug">
                                            {{ $pName }}
                                        </div>
                                        @if ($pId)
                                            <a href="https://www.aliexpress.com/item/{{ $pId }}.html" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 font-medium">
                                                <span>عرض المنتج على موقع AliExpress</span>
                                                <span class="icon-open text-xs">↗</span>
                                            </a>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $pId ?: '-' }}
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ $skuId ?: '-' }}
                                    </td>
                                    <td class="p-4 text-center font-black text-gray-900 dark:text-white text-base">
                                        {{ $pQty }}
                                    </td>
                                    <td class="p-4 ltr:text-right rtl:text-left font-mono font-bold text-gray-800 dark:text-gray-200">
                                        ${{ number_format((float) $unitPrice, 2) }}
                                    </td>
                                    <td class="p-4 ltr:text-right rtl:text-left font-mono font-black text-emerald-600 dark:text-emerald-400 text-base">
                                        ${{ number_format((float) $actualFee, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @elseif ($order->supplierPurchaseOrder && $order->supplierPurchaseOrder->items->count())
                            @foreach ($order->supplierPurchaseOrder->items as $spoItem)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900 dark:text-white">
                                            {{ $spoItem->product?->name ?: 'الصنف #' . $spoItem->supplier_product_id }}
                                        </div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $spoItem->product?->sku }}</div>
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $spoItem->supplier_product_id ?: '-' }}
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs text-gray-600 dark:text-gray-400">
                                        {{ $spoItem->supplier_sku_id ?: '-' }}
                                    </td>
                                    <td class="p-4 text-center font-black text-gray-900 dark:text-white text-base">
                                        {{ $spoItem->qty_ordered }}
                                    </td>
                                    <td class="p-4 ltr:text-right rtl:text-left font-mono font-bold text-gray-800 dark:text-gray-200">
                                        ${{ number_format((float) $spoItem->expected_unit_cost, 2) }}
                                    </td>
                                    <td class="p-4 ltr:text-right rtl:text-left font-mono font-black text-emerald-600 dark:text-emerald-400 text-base">
                                        ${{ number_format((float) ($spoItem->expected_unit_cost * $spoItem->qty_ordered), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-400">
                                    لا توجد بيانات تفصيلية للأصناف
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SECTION 2: Financial & Cost Details --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/40">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">💵</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">البيانات المالية وتفاصيل التكلفة</h2>
                </div>
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-3 py-1 rounded-full border border-emerald-200 dark:border-emerald-800">
                    العملة: {{ $currency }}
                </span>
            </div>

            @php
                $saleFee = $firstItem['sale_fee']['amount'] ?? ($firstItem['product_price']['amount'] ?? '0.00');
                $shippingFee = $firstItem['shipping_fee']['amount'] ?? '0.00';
                $shippingDiscount = $firstItem['shipping_discount_fee']['amount'] ?? '0.00';
                $actualShippingFee = $firstItem['actual_shipping_fee']['amount'] ?? '0.00';
                $actualTaxFee = $firstItem['actual_tax_fee']['amount'] ?? '0.00';
            @endphp

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">تكلفة المنتجات الصافية</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                        ${{ number_format((float) $saleFee, 2) }}
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">رسوم الشحن (قبل الخصم)</span>
                    <div class="text-xl font-bold text-gray-700 dark:text-gray-300 mt-1 font-mono">
                        ${{ number_format((float) $shippingFee, 2) }}
                    </div>
                    @if ((float) $shippingDiscount > 0)
                        <div class="text-xs font-semibold text-emerald-600 mt-1">
                            خصم الشحن: -${{ number_format((float) $shippingDiscount, 2) }}
                        </div>
                    @endif
                </div>

                <div class="bg-gray-50 dark:bg-gray-800/60 rounded-xl p-4 border border-gray-100 dark:border-gray-700/60">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 block">الشحن الفعلي الصافي / الضرائب</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1 font-mono">
                        ${{ number_format((float) $actualShippingFee, 2) }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        الضرائب والرسوم: ${{ number_format((float) $actualTaxFee, 2) }}
                    </div>
                </div>

                @if ($normalizedStatus === 'cancelled')
                    <div class="bg-rose-50 dark:bg-rose-950/40 rounded-xl p-4 border border-rose-200 dark:border-rose-800">
                        <span class="text-xs font-bold text-rose-800 dark:text-rose-300 block uppercase">حالة الدفع المالية</span>
                        <div class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1 font-mono">
                            ${{ number_format((float) $orderAmount, 2) }}
                        </div>
                        <div class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-semibold">
                            لم يتم الدفع (الطلب ملغي)
                        </div>
                    </div>
                @elseif ($normalizedStatus === 'wait_buyer_pay')
                    <div class="bg-amber-50 dark:bg-amber-950/40 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                        <span class="text-xs font-bold text-amber-800 dark:text-amber-300 block uppercase">المبلغ المطلوب للسداد</span>
                        <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1 font-mono">
                            ${{ number_format((float) $orderAmount, 2) }}
                        </div>
                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-semibold">
                            بانتظار السداد في علي إكسبرس
                        </div>
                    </div>
                @else
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
                        <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 block uppercase">الإجمالي النهائي المدفوع</span>
                        <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1 font-mono">
                            ${{ number_format((float) $orderAmount, 2) }}
                        </div>
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">
                            تم السداد بنجاح
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 3: Logistics & Shipping Tracking Details --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/40">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🚚</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">بيانات الشحن والتتبع اللوجستي</h2>
                </div>
                @if ($trackingNum)
                    <span class="text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-3 py-1 rounded-full border border-blue-200 dark:border-blue-800">
                        بوليصة صادرة
                    </span>
                @elseif ($normalizedStatus === 'cancelled')
                    <span class="text-xs font-bold text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 px-3 py-1 rounded-full border border-rose-200 dark:border-rose-800">
                        طلب ملغي
                    </span>
                @elseif ($normalizedStatus === 'wait_buyer_pay')
                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-3 py-1 rounded-full border border-amber-200 dark:border-amber-800">
                        بانتظار السداد
                    </span>
                @endif
            </div>

            <div class="p-6">
                @if ($trackingNum)
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">رقم التتبع الدولي المعتمد</span>
                            <div class="flex items-center gap-3">
                                <span class="text-2xl md:text-3xl font-black font-mono text-gray-900 dark:text-white tracking-wider">
                                    {{ $trackingNum }}
                                </span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ $trackingNum }}'); alert('تم نسخ رقم التتبع بنجاح!');" class="px-2.5 py-1 text-xs bg-white dark:bg-gray-800 hover:bg-gray-100 text-gray-700 dark:text-gray-300 rounded-lg border border-gray-300 dark:border-gray-600 font-medium shadow-sm transition">
                                    نسخ
                                </button>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400 mt-1 flex-wrap">
                                <div><strong class="text-gray-900 dark:text-white">شركة وخدمة الشحن:</strong> <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $carrierText ?: 'شحن قينياو القياسي' }}</span></div>
                                @if ($logisticsStatusText)
                                    <div>&bull;</div>
                                    <div><strong class="text-gray-900 dark:text-white">الحالة اللوجستية:</strong> <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $logisticsStatusText }}</span></div>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3">
                            <a href="https://global.cainiao.com/newDetail.htm?mailNoList={{ $trackingNum }}&otherMailNoList=" target="_blank" class="inline-flex items-center gap-2 px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-md transition duration-150">
                                <span>تتبع الشحنة مباشرة على منصة قينياو الدولية (Cainiao)</span>
                                <span class="text-base">↗</span>
                            </a>
                        </div>
                    </div>
                @elseif ($normalizedStatus === 'cancelled')
                    <div class="text-center py-8 bg-rose-50/50 dark:bg-rose-950/20 rounded-2xl border border-dashed border-rose-300 dark:border-rose-800/60 p-6">
                        <span class="text-4xl block mb-2">🛑</span>
                        <h3 class="text-base font-bold text-rose-900 dark:text-rose-200">الطلب ملغي في علي إكسبرس (لم يتم الشحن)</h3>
                        <p class="text-xs text-rose-700 dark:text-rose-300 mt-1 max-w-lg mx-auto">
                            تم إلغاء أمر الشراء هذا لدى علي إكسبرس
                            @if ($endReasonText)
                                بسبب: <strong>{{ $endReasonText }}</strong>
                            @else
                                نظراً لانتهاء مهلة السداد أو الإلغاء
                            @endif
                            ، وبالتالي لم يتم إصدار أي بوليصة أو شحنة من قبل المورد.
                        </p>
                        <div class="mt-4">
                            <form action="{{ route('admin.procurement.platform_orders.reorder', $order->id) }}" method="POST" class="inline" onsubmit="return confirm('هل تريد إعادة إنشاء أمر الشراء وإرساله مجدداً لعلي إكسبرس؟');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                    <span class="icon-cart"></span>
                                    <span>إعادة إنشاء الطلب مجدداً</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif ($normalizedStatus === 'wait_buyer_pay')
                    <div class="text-center py-8 bg-amber-50/50 dark:bg-amber-950/20 rounded-2xl border border-dashed border-amber-300 dark:border-amber-800/60 p-6">
                        <span class="text-4xl block mb-2">⏳</span>
                        <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">بانتظار سداد قيمة الطلب للمورد</h3>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-1 max-w-lg mx-auto">
                            أمر الشراء تم إنشاؤه بنجاح على علي إكسبرس وهو بانتظار إتمام الدفع. بمجرد سداد الطلب، سيبدأ المورد في تجهيز الشحنة وتوليد بوليصة التتبع تلقائياً.
                        </p>
                    </div>
                @else
                    <div class="text-center py-8 bg-blue-50/50 dark:bg-blue-950/20 rounded-2xl border border-dashed border-blue-300 dark:border-blue-800/60 p-6">
                        <span class="text-4xl block mb-2">📦</span>
                        <h3 class="text-base font-bold text-blue-900 dark:text-blue-200">الشحنة قيد التجهيز لدى المورد</h3>
                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1 max-w-lg mx-auto">
                            تم تأكيد الدفع بنجاح. يقوم المورد حالياً بتجهيز الطلب وتسليمه لشركة الشحن، وسيتم تحديث رقم التتبع واسم الناقل تلقائياً بمجرد إصداره.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 4 & 5: Timestamps and Store Details (2 columns) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Column 1: Timestamps & Milestones --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                    <span class="text-xl">⏱️</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">المواعيد والتوقيتات الزمنية</h2>
                </div>

                <div class="flex flex-col gap-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">تاريخ إنشاء أمر الشراء</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-white">
                            {{ $createdTime ?: '-' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">تاريخ وتوقيت الدفع المعتمد</span>
                        <span class="font-mono font-bold">
                            @if ($paidTime)
                                <span class="text-emerald-600 dark:text-emerald-400">{{ $paidTime }}</span>
                            @elseif ($normalizedStatus === 'cancelled')
                                <span class="text-rose-600 dark:text-rose-400 font-sans text-xs font-semibold">لم يتم السداد (الطلب ملغي)</span>
                            @elseif ($normalizedStatus === 'wait_buyer_pay')
                                <span class="text-amber-600 dark:text-amber-400 font-sans text-xs font-semibold">بانتظار السداد</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </span>
                    </div>

                    @if ($endReasonText)
                        <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50 bg-rose-50/50 dark:bg-rose-950/20 px-2 rounded-lg">
                            <span class="text-rose-700 dark:text-rose-400 font-medium">سبب الإنهاء / الإلغاء</span>
                            <span class="text-xs font-bold text-rose-800 dark:text-rose-300">
                                {{ $endReasonText }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">آخر مزامنة آلية للنظام</span>
                        <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">
                            {{ $order->last_synced_at ? \Carbon\Carbon::parse($order->last_synced_at)->format('Y-m-d H:i:s') : '-' }}
                        </span>
                    </div>

                    @if ($order->payment_deadline_at && $normalizedStatus === 'wait_buyer_pay')
                        <div class="flex items-center justify-between py-2">
                            <span class="text-gray-500 dark:text-gray-400 font-medium">المهلة القصوى للسداد</span>
                            <span class="font-mono font-semibold text-amber-600 dark:text-amber-400">
                                {{ \Carbon\Carbon::parse($order->payment_deadline_at)->format('Y-m-d H:i:s') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Column 2: Store & Supplier Details --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                    <span class="text-xl">🏪</span>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">بيانات متجر المورد والربط</h2>
                </div>

                <div class="flex flex-col gap-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">اسم متجر المورد</span>
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ $storeInfo['store_name'] ?? 'متجر علي إكسبرس' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">معرف المتجر في المنصة</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-white">
                            {{ $storeInfo['store_id'] ?? $order->supplier_store_id ?? '-' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-800/50">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">رابط متجر المورد</span>
                        @if (! empty($storeInfo['store_url']))
                            <a href="{{ $storeInfo['store_url'] }}" target="_blank" class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                <span>زيارة متجر المورد على AliExpress</span>
                                <span>↗</span>
                            </a>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-500 dark:text-gray-400 font-medium">مفتاح الربط الداخلي</span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400">
                            {{ $order->correlation_key ?: '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 6: Raw Diagnostic Payload (Accordion for technical inspection) --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
            <details class="group">
                <summary class="p-5 flex items-center justify-between cursor-pointer list-none bg-gray-50/50 dark:bg-gray-800/40 hover:bg-gray-100/60 dark:hover:bg-gray-800/80 transition-colors">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">🔍</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">سجل البيانات والرد الخام من الـ API (للفحص الفني)</span>
                    </div>
                    <span class="text-xs font-mono text-gray-500 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="p-5 bg-gray-950 text-gray-200 font-mono text-xs overflow-x-auto max-h-96 rounded-b-2xl">
                    <pre>{{ json_encode($liveData ?: $snapshots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </details>
        </div>
    </div>
</x-admin::layouts>
