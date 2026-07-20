<?php

namespace Webkul\Fulfillment\Services\Domain;

use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Repositories\LedgerEntryRepository;

class FinancialSettlementService
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        protected LedgerDomainService $ledgerDomainService,
        protected LedgerEntryRepository $ledgerEntryRepository
    ) {}

    /**
     * Settle prepaid customer deposit.
     */
    public function settlePrepaidInvoice(int $orderId, float $total): void
    {
        $reference = "Invoice paid prepaid: {$orderId}";
        $this->postDoubleEntry($orderId, '1020', '3010', $total, $reference);
    }

    /**
     * Settle gateway clearing commission.
     */
    public function settlePrepaidCommission(int $orderId, float $commission): void
    {
        $reference = "Gateway commission: {$orderId}";
        $this->postDoubleEntry($orderId, '5030', '1020', $commission, $reference);
    }

    /**
     * Settle prepaid order shipment revenue.
     */
    public function settleOrderShipmentPrepaid(int $orderId, float $total): void
    {
        $reference = "Order shipped prepaid: {$orderId}";
        $this->postDoubleEntry($orderId, '3010', '4010', $total, $reference);
    }

    /**
     * Settle Cash-on-Delivery shipment courier receivables.
     */
    public function settleOrderShipmentCOD(int $orderId, float $total): void
    {
        $reference = "Order shipped COD: {$orderId}";
        $this->postDoubleEntry($orderId, '1210', '4010', $total, $reference);
    }

    /**
     * Settle supplier procurement cost of goods sold.
     */
    public function settleSupplierCost(int $orderId, int $purchaseOrderId, float $cost): void
    {
        $reference = "Supplier cost PO: {$purchaseOrderId}";
        $this->postDoubleEntry($orderId, '5010', '2010', $cost, $reference, $purchaseOrderId);
    }

    /**
     * Settle supplier cash payout.
     */
    public function settleSupplierPayment(int $orderId, int $purchaseOrderId, float $cost): void
    {
        $reference = "Supplier payout PO: {$purchaseOrderId}";
        $this->postDoubleEntry($orderId, '2010', '1010', $cost, $reference, $purchaseOrderId);
    }

    /**
     * Settle Cash-on-Delivery courier collection remittance.
     */
    public function settleCourierRemittance(int $orderId, float $collected, float $fee): void
    {
        $reference = "Courier settlement: {$orderId}";
        
        DB::transaction(function () use ($orderId, $collected, $fee, $reference) {
            // First Debit Cash & Bank for the net amount
            $netAmount = $collected - $fee;
            if ($netAmount > 0) {
                $this->postDoubleEntry($orderId, '1010', '1210', $netAmount, $reference);
            }

            // Second Debit Shipping Expense for the delivery fee
            if ($fee > 0) {
                $this->postDoubleEntry($orderId, '5020', '1210', $fee, $reference);
            }
        });
    }

    /**
     * Settle customer deposit refund prior to order shipment.
     */
    public function settleRefundPrepaidBeforeShip(int $orderId, float $total): void
    {
        $reference = "Refund pre-shipment: {$orderId}";
        $this->postDoubleEntry($orderId, '3010', '1010', $total, $reference);
    }

    /**
     * Settle customer refund after shipment (RMA).
     */
    public function settleRefundAfterShip(int $orderId, float $total, bool $restock = false, float $cost = 0.0): void
    {
        $reference = "Refund post-shipment (RMA): {$orderId}";

        DB::transaction(function () use ($orderId, $total, $restock, $cost, $reference) {
            // Reverse revenue
            $this->postDoubleEntry($orderId, '4010', '1010', $total, $reference);

            // Revert COGS if inventory was returned and restocked in warehouse
            if ($restock && $cost > 0) {
                $this->postDoubleEntry($orderId, '1110', '5010', $cost, "Restock inventory: {$orderId}");
            }
        });
    }

    /**
     * Helper to build and persist double ledger entries with idempotency protection.
     */
    protected function postDoubleEntry(
        int $orderId,
        string $debitAccount,
        string $creditAccount,
        float $amount,
        string $reference,
        ?int $purchaseOrderId = null,
        ?string $correlationId = null
    ): void {
        DB::transaction(function () use ($orderId, $debitAccount, $creditAccount, $amount, $reference, $purchaseOrderId, $correlationId) {
            // Idempotency: skip posting if reference for these accounts already exists
            $exists = $this->ledgerEntryRepository->findWhere([
                'order_id'     => $orderId,
                'account_code' => $debitAccount,
                'reference'    => $reference,
            ])->isNotEmpty();

            if ($exists) {
                return;
            }

            if ($correlationId === null) {
                $correlationId = DB::table('order_processes')
                    ->where('order_id', $orderId)
                    ->value('correlation_id');
            }

            $entries = $this->ledgerDomainService->buildDoubleEntry(
                $orderId,
                $debitAccount,
                $creditAccount,
                $amount,
                $reference,
                $purchaseOrderId,
                $correlationId
            );

            foreach ($entries as $entry) {
                $this->ledgerEntryRepository->create($entry->toArray());
            }
        });
    }
}
