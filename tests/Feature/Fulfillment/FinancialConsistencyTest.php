<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\LedgerEntry;
use Webkul\Fulfillment\Services\Domain\FinancialSettlementService;

class FinancialConsistencyTest extends TestCase
{
    protected FinancialSettlementService $settlement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settlement = app(FinancialSettlementService::class);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('order_processes')->delete();
        DB::table('ledger_entries')->delete();
    }

    /**
     * Test prepaid flow ledger consistency, COA closing, and traceability.
     */
    public function test_prepaid_financial_consistency(): void
    {
        $order = Order::factory()->create(['grand_total' => 200.00]);
        $correlationId = 'corr-prepaid-123';

        OrderProcess::create([
            'order_id'        => $order->id,
            'payment_mode'    => 'prepaid',
            'lifecycle_state' => 'fulfillment_started',
            'correlation_id'  => $correlationId,
        ]);

        // 1. Invoiced (Prepaid Deposit)
        $this->settlement->settlePrepaidInvoice($order->id, 200.00);
        $this->settlement->settlePrepaidCommission($order->id, 6.00); // 3% commission

        // Assert Total Debit == Total Credit
        $this->assertLedgerBalanced($order->id);

        // 2. Supplier Cost & Payment
        $this->settlement->settleSupplierCost($order->id, 501, 100.00);
        $this->settlement->settleSupplierPayment($order->id, 501, 100.00);

        $this->assertLedgerBalanced($order->id);

        // 3. Order Shipped (Deposit closed, Revenue recognized)
        $this->settlement->settleOrderShipmentPrepaid($order->id, 200.00);

        $this->assertLedgerBalanced($order->id);

        // Assert Accounts closing properly:
        // Customer Deposits (3010) should be net 0.00
        $depositsBalance = LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('credit')
            - LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('debit');
        $this->assertEquals(0.00, $depositsBalance);

        // Accounts Payable Supplier (2010) should be net 0.00
        $payablesBalance = LedgerEntry::where('order_id', $order->id)->where('account_code', '2010')->sum('credit')
            - LedgerEntry::where('order_id', $order->id)->where('account_code', '2010')->sum('debit');
        $this->assertEquals(0.00, $payablesBalance);

        // Assert Traceability
        $entries = LedgerEntry::where('order_id', $order->id)->get();
        foreach ($entries as $entry) {
            // All entries must link to order_id and correlation_id
            $this->assertEquals($order->id, $entry->order_id);
            $this->assertEquals($correlationId, $entry->correlation_id);

            // Supplier entries must link to purchase_order_id
            if (in_array($entry->account_code, ['2010', '5010']) || str_contains($entry->reference, 'Supplier')) {
                $this->assertEquals(501, $entry->purchase_order_id);
            }
        }
    }

    /**
     * Test prepaid refund prior to shipping.
     */
    public function test_refund_pre_shipment_consistency(): void
    {
        $order = Order::factory()->create(['grand_total' => 150.00]);
        $correlationId = 'corr-refund-preship';

        OrderProcess::create([
            'order_id'        => $order->id,
            'payment_mode'    => 'prepaid',
            'lifecycle_state' => 'fulfillment_started',
            'correlation_id'  => $correlationId,
        ]);

        // Invoice paid
        $this->settlement->settlePrepaidInvoice($order->id, 150.00);

        // Refund pre-shipment
        $this->settlement->settleRefundPrepaidBeforeShip($order->id, 150.00);

        $this->assertLedgerBalanced($order->id);

        // Deposits (3010) closed
        $depositsBalance = LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('credit')
            - LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('debit');
        $this->assertEquals(0.00, $depositsBalance);
    }

    /**
     * Test refund after shipping (RMA).
     */
    public function test_refund_post_shipment_consistency(): void
    {
        $order = Order::factory()->create(['grand_total' => 150.00]);
        $correlationId = 'corr-refund-postship';

        OrderProcess::create([
            'order_id'        => $order->id,
            'payment_mode'    => 'prepaid',
            'lifecycle_state' => 'fulfillment_started',
            'correlation_id'  => $correlationId,
        ]);

        // Invoice -> Shipped -> Refund post-shipment
        $this->settlement->settlePrepaidInvoice($order->id, 150.00);
        $this->settlement->settleOrderShipmentPrepaid($order->id, 150.00);
        $this->settlement->settleRefundAfterShip(orderId: $order->id, total: 150.00, restock: true, cost: 75.00);

        $this->assertLedgerBalanced($order->id);

        // Revenue (4010) closed (net 0.00)
        $revenueBalance = LedgerEntry::where('order_id', $order->id)->where('account_code', '4010')->sum('credit')
            - LedgerEntry::where('order_id', $order->id)->where('account_code', '4010')->sum('debit');
        $this->assertEquals(0.00, $revenueBalance);
    }

    /**
     * Helper to assert that ledger entries for a given order are balanced.
     */
    protected function assertLedgerBalanced(int $orderId): void
    {
        $totalDebit = LedgerEntry::where('order_id', $orderId)->sum('debit');
        $totalCredit = LedgerEntry::where('order_id', $orderId)->sum('credit');

        $this->assertEquals($totalDebit, $totalCredit, "Ledger is unbalanced: Debit = {$totalDebit}, Credit = {$totalCredit}");
    }
}
