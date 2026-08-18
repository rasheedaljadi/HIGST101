<x-admin::layouts>
    <x-slot:title>
        {{ trans('delivery::app.admin.assignments.view-title', ['id' => $assignment->order->increment_id ?? $assignment->order_id]) }}
    </x-slot>

    @php
        $isCod = strtolower((string) ($assignment->order->payment?->method ?? '')) === 'cashondelivery';
        $statusBadge = match($assignment->status) {
            'ready_for_assignment' => ['bg' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200 border-yellow-200', 'text' => 'جاهز للإسناد'],
            'assigned' => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 border-blue-200', 'text' => 'مسند للمندوب'],
            'picked_up' => ['bg' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-200 border-purple-200', 'text' => 'مستلم من المستودع'],
            'out_for_delivery' => ['bg' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200 border-indigo-200', 'text' => 'في الطريق للعميل'],
            'arrived_at_point' => ['bg' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200 border-cyan-200', 'text' => 'وصل لنقطة التسليم'],
            'delivered' => ['bg' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200 border-emerald-200', 'text' => 'تم التسليم بنجاح'],
            'delivery_failed' => ['bg' => 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200 border-rose-200', 'text' => 'تعذر التسليم'],
            'retry_scheduled' => ['bg' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200 border-amber-200', 'text' => 'معاد جدولته'],
            'returned_to_hayest' => ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-300', 'text' => 'مرتجع للمركزي'],
            default => ['bg' => 'bg-gray-100 text-gray-800', 'text' => $assignment->status]
        };
    @endphp

    <div class="flex flex-col gap-6" id="delivery-task-app">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.courier.index') }}" class="hover:text-blue-600">
                        {{ trans('delivery::app.admin.menu.courier-tasks') }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium">
                        طلب #{{ $assignment->order->increment_id ?? $assignment->order_id }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1 flex items-center gap-3">
                    مهمة توصيل طلب #{{ $assignment->order->increment_id ?? $assignment->order_id }}
                    <span class="px-3 py-1 rounded-md text-xs font-bold border {{ $statusBadge['bg'] }}">
                        {{ $statusBadge['text'] }}
                    </span>
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ $assignment->delivery_type === 'delivery_point' ? '📍 تسليم عبر نقطة توزيع معتمدة' : '🏠 توصيل مباشر إلى عنوان العميل' }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.courier.index') }}" class="secondary-button flex items-center gap-2 text-xs py-2 px-3">
                    <span>←</span>
                    العودة لقائمة المهام
                </a>
            </div>
        </div>

        {{-- Alert Notification Banner --}}
        <div id="action-alert" class="hidden p-4 rounded-lg text-sm font-semibold border transition-all"></div>

        {{-- Main Layout Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column (2 Cols wide) --}}
            <div class="lg:col-span-2 flex flex-col gap-6">
                {{-- Customer & Destination Card --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="icon-customer-2 text-lg text-blue-600"></span>
                            بيانات العميل وموقع التسليم
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg flex flex-col gap-1.5">
                            <span class="text-gray-500">اسم العميل:</span>
                            <span class="font-bold text-gray-900 dark:text-white text-sm">
                                {{ $assignment->customer_address_snapshot['first_name'] ?? 'العميل' }} {{ $assignment->customer_address_snapshot['last_name'] ?? '' }}
                            </span>
                        </div>

                        <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg flex flex-col gap-1.5">
                            <span class="text-gray-500">رقم الهاتف والتواصل:</span>
                            @if(!empty($assignment->customer_address_snapshot['phone']))
                                <div class="flex items-center gap-3">
                                    <a href="tel:{{ $assignment->customer_address_snapshot['phone'] }}" class="font-bold text-blue-600 hover:underline flex items-center gap-1 text-sm">
                                        <span>📞</span>
                                        <span>{{ $assignment->customer_address_snapshot['phone'] }}</span>
                                    </a>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $assignment->customer_address_snapshot['phone']) }}" target="_blank" class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">
                                        واتساب
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-400">غير متوفر</span>
                            @endif
                        </div>

                        <div class="col-span-1 md:col-span-2 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg flex flex-col gap-1">
                            <span class="text-gray-500">العنوان بالتفصيل:</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $assignment->customer_address_snapshot['address'] ?? '' }}، {{ $assignment->customer_address_snapshot['city'] ?? '' }} ({{ $assignment->customer_address_snapshot['state'] ?? '' }})
                            </span>
                        </div>

                        @if($assignment->delivery_type === 'delivery_point' && $assignment->delivery_point_snapshot)
                            <div class="col-span-1 md:col-span-2 p-3.5 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 rounded-lg flex flex-col gap-1">
                                <span class="text-blue-700 dark:text-blue-300 font-bold">🏢 نقطة الاستلام المعتمدة:</span>
                                <span class="font-bold text-gray-900 dark:text-white">
                                    {{ $assignment->delivery_point_snapshot['name_ar'] ?? $assignment->delivery_point_snapshot['name'] ?? '' }}
                                </span>
                                <span class="text-gray-600 dark:text-gray-300 text-[11px]">
                                    📍 {{ $assignment->delivery_point_snapshot['address'] ?? '' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Order Products & Shipment Card --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="icon-product-1 text-lg text-blue-600"></span>
                            بنود ومنتجات الشحنة
                        </h2>
                        <span class="text-xs text-gray-500">
                            عدد الأصناف: {{ $assignment->order->items->count() ?? 0 }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-right">
                            <thead class="text-gray-500 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="p-2.5">المنتج</th>
                                    <th class="p-2.5">رمز SKU</th>
                                    <th class="p-2.5 text-center">الكمية</th>
                                    <th class="p-2.5">السعر</th>
                                    <th class="p-2.5">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($assignment->order->items as $item)
                                    <tr>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ $item->name }}</td>
                                        <td class="p-2.5 font-medium text-gray-500">{{ $item->sku }}</td>
                                        <td class="p-2.5 text-center font-bold text-blue-600">{{ $item->qty_ordered }}</td>
                                        <td class="p-2.5 font-medium">{{ core()->formatPrice((float)$item->price, $assignment->order->order_currency_code) }}</td>
                                        <td class="p-2.5 font-bold text-gray-900 dark:text-white">{{ core()->formatPrice((float)$item->total, $assignment->order->order_currency_code) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-gray-400">لا توجد بنود مسجلة للطلب.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Delivery Attempts Timeline --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="icon-report text-lg text-blue-600"></span>
                            سجل محاولات وتتبع مسار التوصيل
                        </h2>
                        <span class="text-xs text-gray-500">
                            المحاولات: {{ $assignment->attempt_count }} من {{ $assignment->max_attempts }}
                        </span>
                    </div>

                    @if($assignment->attemptLogs->isEmpty())
                        <p class="text-xs text-gray-400 p-3 bg-gray-50 dark:bg-gray-800/40 rounded-lg text-center">
                            لم تُسجل أي محاولات توصيل سابقة بعد.
                        </p>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach($assignment->attemptLogs as $log)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800/60 rounded-lg border border-gray-100 dark:border-gray-800 flex items-start justify-between gap-3 text-xs">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-900 dark:text-white">محاولة #{{ $log->attempt_number }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $log->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : ($log->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ $log->status }}
                                            </span>
                                        </div>
                                        @if($log->failure_reason)
                                            <span class="text-rose-600 dark:text-rose-400 font-medium">
                                                سبب التعثر: {{ $log->failure_reason }}
                                            </span>
                                        @endif
                                        @if($log->notes)
                                            <span class="text-gray-500">ملاحظات: {{ $log->notes }}</span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-gray-400 shrink-0">
                                        {{ core()->formatDate($log->attempted_at, 'Y-m-d H:i') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column (1 Col wide) --}}
            <div class="flex flex-col gap-6">
                {{-- Financial & Collection Card --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3 flex items-center gap-2">
                        <span class="icon-sales text-lg text-emerald-600"></span>
                        البيانات المالية والتحصيل
                    </h2>

                    <div class="flex flex-col gap-2.5 text-xs">
                        <div class="flex justify-between text-gray-500">
                            <span>قيمة المنتجات:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ core()->formatPrice((float)$assignment->order->sub_total, $assignment->order->order_currency_code) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>رسوم التوصيل:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ core()->formatPrice((float)$assignment->order->shipping_amount, $assignment->order->order_currency_code) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>الخصم:</span>
                            <span class="font-medium text-rose-600">{{ core()->formatPrice((float)$assignment->order->discount_amount, $assignment->order->order_currency_code) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-900 dark:text-white font-bold pt-2 border-t text-sm">
                            <span>الإجمالي الكلي:</span>
                            <span class="text-blue-600">{{ core()->formatPrice((float)$assignment->order->grand_total, $assignment->order->order_currency_code) }}</span>
                        </div>
                    </div>

                    {{-- COD Highlight Box --}}
                    <div class="p-3.5 rounded-lg border {{ $isCod ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-900/60' : 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-900/60' }}">
                        <div class="flex flex-col gap-1 text-center">
                            <span class="text-xs font-bold {{ $isCod ? 'text-amber-800 dark:text-amber-200' : 'text-emerald-800 dark:text-emerald-200' }}">
                                {{ $isCod ? '💵 المبلغ المطلوب تحصيله نقداً:' : '✅ الطلب مدفوع إلكترونياً بالكامل' }}
                            </span>
                            @if($isCod)
                                <span class="text-xl font-extrabold text-amber-900 dark:text-amber-100 mt-1">
                                    {{ core()->formatPrice((float)$assignment->order->grand_total, $assignment->order->order_currency_code) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Panel Card --}}
                <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3 flex items-center gap-2">
                        <span class="icon-attribute-block text-lg text-blue-600"></span>
                        إجراءات المندوب الميدانية
                    </h2>

                    @if($assignment->status === 'assigned')
                        <p class="text-xs text-gray-500">
                            اضغط لبدء مسار التوصيل وتأكيد خروج الشحنة معك في خط السير.
                        </p>
                        <button type="button" onclick="executeDeliveryAction('{{ route('admin.courier.start', $assignment->id) }}', {})" class="primary-button w-full justify-center py-2.5 text-xs font-bold bg-blue-600 hover:bg-blue-700">
                            🚀 بدء خط السير والتوصيل
                        </button>

                    @elseif($assignment->status === 'picked_up' || $assignment->status === 'out_for_delivery')
                        @if($assignment->delivery_type === 'delivery_point')
                            <p class="text-xs text-gray-500">
                                عند وصولك لنقطة التوزيع، اضغط لتأكيد التسليم لنقطة الاستلام.
                            </p>
                            <button type="button" onclick="executeDeliveryAction('{{ route('admin.courier.arrived_point', $assignment->id) }}', {})" class="primary-button w-full justify-center py-2.5 text-xs font-bold bg-cyan-600 hover:bg-cyan-700">
                                📍 تأكيد الوصول وتسليم نقطة التوزيع
                            </button>
                        @else
                            {{-- Success Action --}}
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 rounded-lg flex flex-col gap-2">
                                <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                    تأكيد التسليم الناجح للعميل:
                                </span>
                                @if($isCod)
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[11px] text-gray-600 dark:text-gray-300">المبلغ المحصل فعلياً:</label>
                                        <input type="number" step="0.01" id="collected-amount" value="{{ (float)$assignment->order->grand_total }}" class="w-full px-2.5 py-1.5 rounded border border-gray-300 text-xs bg-white dark:bg-gray-800">
                                    </div>
                                @endif
                                <button type="button" onclick="submitDeliveredAction()" class="primary-button w-full justify-center py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 mt-1">
                                    ✅ تم التسليم للعميل والتحصيل
                                </button>
                            </div>

                            {{-- Failure / Postpone Action --}}
                            <div class="p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 rounded-lg flex flex-col gap-2 mt-2">
                                <span class="text-xs font-bold text-rose-800 dark:text-rose-200">
                                    في حال تعذر أو تأجيل التسليم:
                                </span>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[11px] text-gray-600 dark:text-gray-300">سبب التعثر:</label>
                                    <select id="failure-reason" class="w-full px-2.5 py-1.5 rounded border border-gray-300 text-xs bg-white dark:bg-gray-800">
                                        <option value="العميل لا يرد على الهاتف">العميل لا يرد على الهاتف</option>
                                        <option value="طلب العميل تأجيل موعد الاستلام">طلب العميل تأجيل موعد الاستلام</option>
                                        <option value="العنوان غير واضح أو خارج التغطية">العنوان غير واضح أو خارج التغطية</option>
                                        <option value="رفض العميل استلام الطلب">رفض العميل استلام الطلب</option>
                                        <option value="المبلغ غير متوفر مع العميل">المبلغ غير متوفر مع العميل</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="checkbox" id="schedule-retry" checked class="rounded border-gray-300 text-blue-600">
                                    <label for="schedule-retry" class="text-[11px] text-gray-700 dark:text-gray-300">جدولة محاولة توصيل أخرى لاحقاً</label>
                                </div>
                                <button type="button" onclick="submitFailureAction()" class="primary-button w-full justify-center py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 mt-1">
                                    ⚠️ تسجيل تعثر التسليم
                                </button>
                            </div>
                        @endif

                    @elseif($assignment->status === 'arrived_at_point')
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 rounded-lg flex flex-col gap-2">
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                تسليم العميل من نقطة الاستلام:
                            </span>
                            <button type="button" onclick="submitDeliveredAction()" class="primary-button w-full justify-center py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700">
                                ✅ تم تسليم العميل من النقطة
                            </button>
                        </div>

                    @elseif($assignment->status === 'delivered')
                        <div class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-center flex flex-col items-center gap-2">
                            <span class="text-2xl">🎉</span>
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                تم تسليم هذه الشحنة وإغلاق المهمة بنجاح.
                            </span>
                        </div>

                    @elseif($assignment->status === 'delivery_failed')
                        <div class="p-4 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-center flex flex-col items-center gap-2">
                            <span class="text-2xl">⚠️</span>
                            <span class="text-xs font-bold text-rose-800 dark:text-rose-200">
                                تعذر تسليم هذا الطلب. بانتظار توجيه المشرف أو إعادة الجدولة.
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function showAlert(msg, isSuccess = true) {
            const el = document.getElementById('action-alert');
            el.className = isSuccess 
                ? 'p-4 rounded-lg text-sm font-semibold border bg-emerald-50 text-emerald-800 border-emerald-200' 
                : 'p-4 rounded-lg text-sm font-semibold border bg-rose-50 text-rose-800 border-rose-200';
            el.innerHTML = msg;
            el.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function executeDeliveryAction(url, payload) {
            if (!confirm('هل أنت متأكد من تنفيذ هذا الإجراء؟')) return;

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showAlert(data.message || 'تم تنفيذ الإجراء بنجاح!', true);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showAlert(data.message || 'حدث خطأ أثناء تنفيذ الإجراء.', false);
                }
            } catch (err) {
                showAlert('فشل الاتصال بالخادم: ' + err.message, false);
            }
        }

        function submitDeliveredAction() {
            const amountInput = document.getElementById('collected-amount');
            const payload = {};
            if (amountInput) {
                payload.collected_amount = amountInput.value;
            }
            executeDeliveryAction('{{ route('admin.courier.delivered', $assignment->id) }}', payload);
        }

        function submitFailureAction() {
            const reason = document.getElementById('failure-reason').value;
            const retry = document.getElementById('schedule-retry').checked;
            executeDeliveryAction('{{ route('admin.courier.fail', $assignment->id) }}', {
                reason: reason,
                schedule_retry: retry ? 1 : 0
            });
        }
    </script>
    @endpush
</x-admin::layouts>
