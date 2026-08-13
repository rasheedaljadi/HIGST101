<?php

namespace Webkul\Wallet\Services;

class PaymentVerificationService
{
    public const STATE_PAID = 'paid';

    public const STATE_PENDING = 'pending';

    public const STATE_PENDING_PAYMENT = 'pending_payment';

    public const STATE_REFUNDED = 'refunded';

    /**
     * Authoritative invoice verification for promotional eligibility.
     *
     * In Bagisto 2.4.x, the official database column on `invoices` is `state`.
     * Any external or legacy `status` attribute is treated purely as defensive metadata.
     */
    public function isInvoiceEligibleForPromotion(object $invoice, array $metadata = []): bool
    {
        // 1. Authoritative DB Column Check: invoices.state must be 'paid'
        $state = $invoice->state ?? null;
        if ($state !== self::STATE_PAID) {
            return false;
        }

        // 2. Defensive Metadata Check: Inspect external / runtime status if provided
        $externalStatus = $metadata['status'] ?? ($invoice->status ?? null);
        if ($externalStatus !== null) {
            $normalizedStatus = strtolower(trim((string) $externalStatus));

            // Contradictory metadata (e.g. state = paid, but status = pending/failed) MUST be rejected!
            if (in_array($normalizedStatus, ['pending', 'pending_payment', 'failed', 'cancelled', 'unpaid'], true)) {
                return false;
            }
        }

        // 3. Payment Method Check (e.g., Cash on Delivery / Offline)
        $paymentMethod = $metadata['payment_method'] ?? ($invoice->order->payment->method ?? null);
        if ($paymentMethod === 'cashondelivery') {
            // Uncollected COD invoices must never be allowed (already caught by state !== 'paid')
            if ($state !== self::STATE_PAID) {
                return false;
            }
        }

        return true;
    }
}
