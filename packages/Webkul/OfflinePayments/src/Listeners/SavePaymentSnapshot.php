<?php

namespace Webkul\OfflinePayments\Listeners;

use Webkul\OfflinePayments\Repositories\OfflinePaymentDestinationRepository;
use Webkul\Sales\Models\Order;

class SavePaymentSnapshot
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected OfflinePaymentDestinationRepository $destinationRepository
    ) {}

    /**
     * Handle the sales.order.place.after event.
     */
    public function handle(Order $order): void
    {
        $payment = $order->payment;

        if (! $payment || ! in_array($payment->method, ['offline_payments', 'moneytransfer'])) {
            return;
        }

        $additional = $payment->additional;
        $destinationId = null;

        if (is_array($additional)) {
            $destinationId = $additional['selected_offline_destination_id']
                ?? $additional['selected_offline_account_id']
                ?? null;
        }

        if (! $destinationId) {
            return;
        }

        $destination = $this->destinationRepository->find($destinationId);

        if (! $destination || ! $destination->account) {
            return;
        }

        $account = $destination->account;

        // Build self-contained versioned snapshot array (Schema Version 2)
        $snapshot = [
            'snapshot_type' => 'offline_payment',
            'schema_version' => 2,
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'display_name' => $account->display_name,
                'provider_name' => $account->provider_name,
                'recipient_name' => $account->recipient_name,
                'logo_path' => $account->logo_path,
            ],
            'destination' => [
                'id' => $destination->id,
                'account_identifier' => $destination->account_identifier,
                'swift_code' => $destination->swift_code,
                'transfer_instructions' => $destination->transfer_instructions,
            ],
            'currency' => [
                'id' => $destination->currency?->id,
                'code' => $destination->currency?->code,
                'name' => $destination->currency?->name,
            ],
        ];

        $payment->additional = array_merge(is_array($additional) ? $additional : [], [
            'offline_payment_snapshot' => $snapshot,
        ]);

        $payment->save();
    }
}
