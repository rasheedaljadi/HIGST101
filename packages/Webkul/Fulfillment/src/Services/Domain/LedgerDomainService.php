<?php

namespace Webkul\Fulfillment\Services\Domain;

use Webkul\Fulfillment\Exceptions\UnbalancedLedgerException;
use Webkul\Fulfillment\Models\LedgerEntry;

class LedgerDomainService
{
    /**
     * Build draft double entry records after validating invariants.
     *
     * @param  int  $orderId
     * @param  string  $debitAccount
     * @param  string  $creditAccount
     * @param  float  $amount
     * @param  string|null  $reference
     * @return array<\Webkul\Fulfillment\Models\LedgerEntry>
     *
     * @throws \Webkul\Fulfillment\Exceptions\UnbalancedLedgerException
     */
    public function buildDoubleEntry(
        int $orderId,
        string $debitAccount,
        string $creditAccount,
        float $amount,
        ?string $reference = null,
        ?int $purchaseOrderId = null,
        ?string $correlationId = null
    ): array {
        if ($amount <= 0) {
            throw new UnbalancedLedgerException("Ledger entry amount must be greater than zero.");
        }

        if ($debitAccount === $creditAccount) {
            throw new UnbalancedLedgerException("Debit and Credit accounts cannot be the same: {$debitAccount}.");
        }

        // Build un-persisted draft model entries
        $debitEntry = new LedgerEntry([
            'order_id'          => $orderId,
            'purchase_order_id' => $purchaseOrderId,
            'correlation_id'    => $correlationId,
            'account_code'      => $debitAccount,
            'debit'             => $amount,
            'credit'            => 0.00,
            'reference'         => $reference,
        ]);

        $creditEntry = new LedgerEntry([
            'order_id'          => $orderId,
            'purchase_order_id' => $purchaseOrderId,
            'correlation_id'    => $correlationId,
            'account_code'      => $creditAccount,
            'debit'             => 0.00,
            'credit'            => $amount,
            'reference'         => $reference,
        ]);

        return [$debitEntry, $creditEntry];
    }
}
