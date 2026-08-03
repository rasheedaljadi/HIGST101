<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\RefundedNotification;

class Refund extends Base
{
    /**
     * After order is created
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return void
     */
    public function afterCreated($refund)
    {
        $this->refundOrder($refund);

        try {
            if (! core()->getConfigData('emails.general.notifications.emails.general.notifications.new_refund_mail_to_admin')) {
                return;
            }

            $this->prepareMail($refund, new RefundedNotification($refund));
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * After Refund is created
     *
     * [HIGEST WALLET — D-003] Gateway refund disabled intentionally.
     * All refunds are credited to HIGEST Wallet via CreditWalletOnRefundCreated listener.
     * Do NOT re-enable PayPal refund here — it would cause double-refund (PayPal + Wallet).
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return void
     */
    public function refundOrder($refund)
    {
        // Disabled: PayPal gateway refund removed per HIGEST Wallet D-003 decision.
        // All refunds are now credited to customer's HIGEST Wallet automatically.
    }
}
