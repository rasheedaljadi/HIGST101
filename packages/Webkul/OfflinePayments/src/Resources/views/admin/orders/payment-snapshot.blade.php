@if (! empty($order->payment))
    @php
        $additional = $order->payment->additional;
        $rawSnapshot = $additional['offline_payment_snapshot'] ?? null;
        $reader = app(\Webkul\OfflinePayments\Services\OfflinePaymentSnapshotReader::class);
        $snapshot = $reader->read($rawSnapshot);
        $isPaid = $order->payment->method === 'wallet' || $order->total_due == 0 || $order->invoices->count() > 0;
    @endphp

    <div class="mt-4 p-5 border rounded-2xl bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 shadow-sm flex flex-col gap-4">
        {{-- Section Title & Status Badge --}}
        <div class="flex items-center justify-between border-b pb-3 border-gray-100 dark:border-gray-800">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                💳 تفاصيل وإدارة حالة الدفع للطلب
            </h4>

            @if ($isPaid)
                <span class="px-3 py-1 text-xs font-bold text-emerald-800 bg-emerald-100 rounded-full border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800 inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    مؤكدة (تم الدفع)
                </span>
            @else
                <span class="px-3 py-1 text-xs font-bold text-amber-800 bg-amber-100 rounded-full border border-amber-300 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800 inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                    غير مؤكدة (قيد انتظار مراجعة الأدمن)
                </span>
            @endif
        </div>

        {{-- Account & Snapshot Info --}}
        @if (! empty($snapshot))
            <div class="flex items-center gap-3">
                @if (! empty($snapshot['account_logo_path']))
                    <img src="{{ Storage::url($snapshot['account_logo_path']) }}" class="h-10 w-10 rounded-lg border object-contain bg-white p-1" alt="Logo">
                @endif
                <div>
                    <p class="text-sm font-bold text-gray-800 dark:text-white">
                        {{ $snapshot['account_display_name'] }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $snapshot['account_provider_name'] }}
                    </p>
                </div>
            </div>

            <div class="text-xs text-gray-600 dark:text-gray-300 space-y-1.5 bg-gray-50 dark:bg-gray-800/60 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                <p><strong>@lang('offline_payments::app.admin.form.recipient-name'):</strong> {{ $snapshot['account_recipient_name'] }}</p>
                <p><strong>@lang('offline_payments::app.admin.form.account-identifier'):</strong> <code class="font-bold text-gray-900 dark:text-white">{{ $snapshot['account_identifier'] }}</code></p>
                @if (! empty($snapshot['swift_code']))
                    <p><strong>SWIFT:</strong> {{ $snapshot['swift_code'] }}</p>
                @endif
            </div>
        @endif

        {{-- Customer Uploaded Receipt Screenshot --}}
        <div class="pt-2">
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                🖼️ <span>إشعار / وصل التحويل المالي المرفق من العميل:</span>
            </p>

            @if (! empty($additional['receipt_path']))
                <div class="relative group inline-block">
                    <a href="{{ Storage::url($additional['receipt_path']) }}" target="_blank" title="اضغط لفتح الصورة بالحجم الكامل">
                        <img src="{{ Storage::url($additional['receipt_path']) }}" class="max-h-56 max-w-full rounded-xl border-2 border-blue-200 dark:border-gray-700 object-contain shadow-md hover:opacity-95 transition-opacity bg-white p-1" alt="إشعار التحويل المالي">
                        <span class="mt-1.5 block text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                            🔍 معاينة إشعار العميل بالحجم الكامل ↗
                        </span>
                    </a>
                </div>
            @else
                <div class="p-3.5 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60">
                    <p class="text-xs font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                        ⚠️ <span>لم يتم إرفاق صورة إشعار تحويل مالي من قبل العميل عند تقديم هذا الطلب.</span>
                    </p>
                </div>
            @endif
        </div>

        {{-- Admin Payment Control Form (Confirm OR Reject) --}}
        @if (! $isPaid)
            <div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2.5">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                    إجراءات الأدمن لمعالجة الطلب بعد معاينة الإشعار:
                </p>

                <div class="flex items-center gap-3 flex-wrap">
                    {{-- 1. Confirm & Accept Payment Button --}}
                    @if ($order->canInvoice())
                        <form method="POST" action="{{ route('admin.sales.invoices.store', $order->id) }}">
                            @csrf
                            <input type="hidden" name="can_create_transaction" value="1" />
                            @foreach ($order->items as $item)
                                <input type="hidden" name="invoice[items][{{ $item->id }}]" value="{{ $item->qty_to_invoice }}" />
                            @endforeach

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-xl transition-all shadow-sm cursor-pointer border-0"
                                onclick="return confirm('هل أنت متأكد من صحة ومطابقة إشعار التحويل لتأكيد عملية الدفع؟')"
                            >
                                <span>🚀 اعتماد وتأكيد الدفع (تحديث الحالة لمؤكدة)</span>
                            </button>
                        </form>
                    @endif

                    {{-- 2. Reject Payment & Cancel Order Button --}}
                    @if ($order->canCancel())
                        <form method="POST" action="{{ route('admin.sales.orders.cancel', $order->id) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-xl transition-all shadow-sm cursor-pointer border-0"
                                onclick="return confirm('هل أنت متأكد من عدم صحة الإشعار ورفض عملية الدفع وإلغاء هذا الطلب؟')"
                            >
                                <span>✕ رفض عملية الدفع وإلغاء الطلب</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif
