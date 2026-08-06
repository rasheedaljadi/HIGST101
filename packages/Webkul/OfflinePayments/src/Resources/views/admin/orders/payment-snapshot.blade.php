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

        {{-- Customer Receipt Screenshot --}}
        @if (! empty($additional['receipt_path']))
            <div class="pt-2">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-1.5">
                    🖼️ <span>إشعار / وصل التحويل المالي المرفق من العميل:</span>
                </p>
                <div class="relative group inline-block">
                    <a href="{{ Storage::url($additional['receipt_path']) }}" target="_blank" title="اضغط لفتح الصورة بالحجم الكامل">
                        <img src="{{ Storage::url($additional['receipt_path']) }}" class="max-h-48 max-w-full rounded-xl border-2 border-blue-200 dark:border-gray-700 object-contain shadow-md hover:opacity-90 transition-opacity bg-white p-1" alt="إشعار التحويل المالي">
                        <span class="mt-1 block text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                            🔍 معاينة الصورة بالحجم الكامل ↗
                        </span>
                    </a>
                </div>
            </div>
        @endif

        {{-- Admin Payment Control Form --}}
        @if (! $isPaid && $order->canInvoice())
            <div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-800 flex flex-col gap-2">
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">
                    التحكم في حالة الدفع وإدارة الطلب:
                </p>

                <form method="POST" action="{{ route('admin.sales.invoices.store', $order->id) }}" class="flex items-center gap-3 flex-wrap">
                    @csrf
                    @foreach ($order->items as $item)
                        <input type="hidden" name="invoice[items][{{ $item->id }}]" value="{{ $item->qty_to_invoice }}" />
                    @endforeach

                    <button
                        type="submit"
                        class="primary-button text-xs py-2 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold inline-flex items-center gap-1.5 shadow-sm"
                        onclick="return confirm('هل أنت تأكد من اعتماد ومطابقة إشعار التحويل المالي لتأكيد دفع الطلب؟')"
                    >
                        <span>🚀 اعتماد وتأكيد الدفع (تحديث الحالة لمؤكدة)</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
@endif
