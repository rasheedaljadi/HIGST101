<?php

namespace Webkul\Fulfillment\Listeners;

use Webkul\Fulfillment\Repositories\LedgerEntryRepository;
use Webkul\Fulfillment\Services\Domain\LedgerDomainService;

class LedgerListener
{
    /**
     * Create a new listener instance.
     */
    public function __construct(
        protected LedgerDomainService $ledgerDomainService,
        protected LedgerEntryRepository $ledgerEntryRepository
    ) {}

    /**
     * Handle the outbox event and build/save double ledger entries.
     *
     * @param  string  $eventName
     * @param  array  $payload
     * @param  string  $correlationId
     * @param  string  $causationId
     * @return void
     */
    public function handle(string $eventName, array $payload, string $correlationId, string $causationId, string $outboxEventId = null): void
    {
        if (config('app.ledger_fail_sim')) {
            throw new \RuntimeException("Simulated ledger database failure");
        }

        $orderId = $payload['order_id'] ?? null;
        $amount = 100.00;
        if ($eventName === 'OrderAllocationReserved' || $eventName === 'OrderAllocationReleased') {
            $amount = (float) ($payload['revenue_amount'] ?? (isset($payload['quantity']) ? ($payload['quantity'] * 20.00) : 100.00));
        } elseif (in_array($eventName, ['SupplierOrderSubmitted', 'SupplierOrderPaid', 'SupplierOrderRefunded'], true)) {
            $amount = (float) ($payload['supplier_cost'] ?? 100.00);
        }

        $purchaseOrderId = $payload['purchase_order_id'] ?? null;

        $reference = ($eventName === 'OrderAllocationReserved')
            ? 'Allocation reserved: ' . $outboxEventId
            : (($eventName === 'OrderAllocationReleased')
                ? 'Reversal - Allocation released: ' . $outboxEventId
                : $eventName . ': ' . $outboxEventId);

        // Idempotency check: verify if the entries with this reference already exist
        $existing = $this->ledgerEntryRepository->findWhere(['reference' => $reference])->first();
        if ($existing) {
            return;
        }

        if ($eventName === 'OrderAllocationReserved') {
            // Debit: Cash/Receivables (1010), Credit: Inventory/Revenue (4010)
            $entries = $this->ledgerDomainService->buildDoubleEntry($orderId, '1010', '4010', $amount, $reference, $purchaseOrderId, $correlationId);
            
            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        } elseif ($eventName === 'OrderAllocationReleased') {
            // Reverse entries: Debit: Inventory/Revenue (4010), Credit: Cash/Receivables (1010)
            $entries = $this->ledgerDomainService->buildDoubleEntry($orderId, '4010', '1010', $amount, $reference, $purchaseOrderId, $correlationId);
            
            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        } elseif ($eventName === 'SupplierOrderSubmitted') {
            // Debit: COGS Pending (5010), Credit: Supplier Payable (2010)
            $entries = $this->ledgerDomainService->buildDoubleEntry($orderId, '5010', '2010', $amount, $reference, $purchaseOrderId, $correlationId);
            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        } elseif ($eventName === 'SupplierOrderPaid') {
            // Debit: Supplier Payable (2010), Credit: Cash (1010)
            $entries = $this->ledgerDomainService->buildDoubleEntry($orderId, '2010', '1010', $amount, $reference, $purchaseOrderId, $correlationId);
            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        } elseif ($eventName === 'SupplierOrderRefunded') {
            // Reversal: Debit: Supplier Payable (2010), Credit: COGS Pending (5010)
            $entries = $this->ledgerDomainService->buildDoubleEntry($orderId, '2010', '5010', $amount, $reference, $purchaseOrderId, $correlationId);
            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        }
    }
}
