@if (! empty($order->payment) && $order->payment->method === 'offline_payments')
    @php
        $additional = $order->payment->additional;
        $rawSnapshot = $additional['offline_payment_snapshot'] ?? null;
        $reader = app(\Webkul\OfflinePayments\Services\OfflinePaymentSnapshotReader::class);
        $snapshot = $reader->read($rawSnapshot);
    @endphp

    @if (! empty($snapshot))
        <div class="mt-4 p-4 border rounded-xl bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-800 dark:text-white mb-2">
                @lang('offline_payments::app.admin.orders.payment-snapshot-title')
            </p>

            <div class="flex items-center gap-3 mb-3">
                @if (! empty($snapshot['account_logo_path']))
                    <img src="{{ Storage::url($snapshot['account_logo_path']) }}" class="h-10 w-10 rounded border object-cover bg-white" alt="Logo">
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

            <div class="text-xs text-gray-600 dark:text-gray-300 space-y-1">
                <p><strong>@lang('offline_payments::app.admin.form.recipient-name'):</strong> {{ $snapshot['account_recipient_name'] }}</p>
                <p><strong>@lang('offline_payments::app.admin.form.account-identifier'):</strong> {{ $snapshot['account_identifier'] }}</p>
                @if (! empty($snapshot['swift_code']))
                    <p><strong>SWIFT:</strong> {{ $snapshot['swift_code'] }}</p>
                @endif
                @if (! empty($snapshot['currency_code']))
                    <p><strong>@lang('offline_payments::app.admin.form.currency'):</strong> {{ $snapshot['currency_code'] }} {{ ! empty($snapshot['currency_name']) ? '(' . $snapshot['currency_name'] . ')' : '' }}</p>
                @endif
                @if (! empty($snapshot['transfer_instructions']))
                    <div class="mt-2 pt-2 border-t text-gray-500 whitespace-pre-line">
                        <strong>@lang('offline_payments::app.admin.form.transfer-instructions'):</strong><br>
                        {{ $snapshot['transfer_instructions'] }}
                    </div>
                @endif
            </div>
        </div>
    @endif
@endif
