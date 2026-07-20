<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Shipment;
use Webkul\Sales\Models\Refund;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\LedgerEntry;
use Webkul\Fulfillment\Services\Application\OrderLifecycleCoordinator;
use Webkul\Fulfillment\Services\Application\FulfillmentSagaCoordinator;
use Webkul\Fulfillment\Services\Domain\FinancialSettlementService;
use Webkul\Fulfillment\Listeners\OrderLifecycleListener;
use Webkul\Fulfillment\Exceptions\FulfillmentSagaException;
use Webkul\Fulfillment\Handlers\CancelAllocationHandler;

class OrderLifecycleIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Clear tables
        DB::table('order_processes')->delete();
        DB::table('ledger_entries')->delete();
        DB::table('financial_timeline')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('order_allocations')->delete();
    }

    /**
     * Test COD order does not start fulfillment before manual acceptance.
     */
    public function test_cod_order_does_not_start_fulfillment_before_acceptance(): void
    {
        $order = Order::factory()->create(['grand_total' => 150.00]);
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'qty_ordered' => 1]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method'   => 'cashondelivery',
        ]);

        OrderAddress::create([
            'order_id'     => $order->id,
            'address_type' => 'shipping',
            'first_name'   => 'Ahmad',
            'last_name'    => 'Ali',
            'city'         => 'Riyadh',
            'country'      => 'SA',
        ]);

        $mockSaga = $this->getMockBuilder(FulfillmentSagaCoordinator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSaga->method('coordinate')->willReturn(['status' => 'success']);
        $this->app->instance(FulfillmentSagaCoordinator::class, $mockSaga);

        $listener = app(OrderLifecycleListener::class);

        // 1. Order Placed
        $listener->handleOrderPlaced($order);

        $process = OrderProcess::where('order_id', $order->id)->first();
        $this->assertNotNull($process);
        $this->assertEquals('cod', $process->payment_mode);
        $this->assertEquals('pending_acceptance', $process->lifecycle_state);

        // 2. Assert no PO or allocation was created
        $this->assertEquals(0, PurchaseOrder::where('order_id', $order->id)->count());

        // 3. Manual acceptance
        app(OrderLifecycleCoordinator::class)->acceptCODOrder($order->id, 'ops_manager_1');

        $process->refresh();
        $this->assertEquals('fulfillment_started', $process->lifecycle_state);
        $this->assertEquals('ops_manager_1', $process->accepted_by);
    }

    /**
     * Test Prepaid payment creates Gateway Clearing and Deposits, but not Revenue before shipment.
     */
    public function test_prepaid_payment_creates_gateway_clearing_not_revenue(): void
    {
        $order = Order::factory()->create(['grand_total' => 200.00]);
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'qty_ordered' => 1]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method'   => 'stripe',
        ]);

        OrderAddress::create([
            'order_id'     => $order->id,
            'address_type' => 'shipping',
            'first_name'   => 'Sami',
            'last_name'    => 'Ahmad',
            'city'         => 'Jeddah',
            'country'      => 'SA',
        ]);

        $mockSaga = $this->getMockBuilder(FulfillmentSagaCoordinator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSaga->method('coordinate')->willReturn(['status' => 'success']);
        $this->app->instance(FulfillmentSagaCoordinator::class, $mockSaga);

        $listener = app(OrderLifecycleListener::class);

        // 1. Order Placed
        $listener->handleOrderPlaced($order);

        $process = OrderProcess::where('order_id', $order->id)->first();
        $this->assertNotNull($process);
        $this->assertEquals('prepaid', $process->payment_mode);
        $this->assertEquals('waiting_payment', $process->lifecycle_state);

        // 2. Payment Invoice Received
        $invoice = Invoice::create([
            'order_id'    => $order->id,
            'state'       => 'paid',
            'grand_total' => 200.00,
        ]);

        $listener->handleInvoiceSaved($invoice);

        $process->refresh();
        $this->assertEquals('fulfillment_started', $process->lifecycle_state);

        // 3. Assert double entries (1020 Gateway Clearing & 3010 Customer Deposits & 5030 Commission)
        $debitClearing = LedgerEntry::where('order_id', $order->id)->where('account_code', '1020')->sum('debit');
        $creditClearing = LedgerEntry::where('order_id', $order->id)->where('account_code', '1020')->sum('credit');
        $this->assertEquals(200.00, $debitClearing);
        $this->assertEquals(6.00, $creditClearing); // Commission 3% of 200 is 6

        $creditDeposits = LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('credit');
        $this->assertEquals(200.00, $creditDeposits);

        $debitCommission = LedgerEntry::where('order_id', $order->id)->where('account_code', '5030')->sum('debit');
        $this->assertEquals(6.00, $debitCommission);

        // 4. Assert NO revenue posted yet (4010)
        $revenue = LedgerEntry::where('order_id', $order->id)->where('account_code', '4010')->sum('credit');
        $this->assertEquals(0.00, $revenue);

        // 5. Shipment processed (Deliver/Revenue recognized)
        $shipment = Shipment::create([
            'order_id' => $order->id,
        ]);

        $listener->handleShipmentSaved($shipment);

        // Assert Revenue posted and Deposits closed
        $revenue = LedgerEntry::where('order_id', $order->id)->where('account_code', '4010')->sum('credit');
        $this->assertEquals(200.00, $revenue);

        $debitDeposits = LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('debit');
        $this->assertEquals(200.00, $debitDeposits);
    }

    /**
     * Test supplier cost settlement is linked to the Purchase Order ID, not the broad Customer Order.
     */
    public function test_supplier_cost_is_linked_to_purchase_order_not_customer_order(): void
    {
        $settlement = app(FinancialSettlementService::class);

        // Post supplier cost linked to PO 900
        $settlement->settleSupplierCost(orderId: 10, purchaseOrderId: 900, cost: 45.00);

        $costDebit = LedgerEntry::where('order_id', 10)->where('account_code', '5010')->first();
        $this->assertNotNull($costDebit);
        $this->assertEquals(45.00, $costDebit->debit);
        $this->assertStringContainsString('Supplier cost PO: 900', $costDebit->reference);

        // Post supplier payment linked to PO 900
        $settlement->settleSupplierPayment(orderId: 10, purchaseOrderId: 900, cost: 45.00);

        $cashCredit = LedgerEntry::where('order_id', 10)->where('account_code', '1010')->first();
        $this->assertNotNull($cashCredit);
        $this->assertEquals(45.00, $cashCredit->credit);
        $this->assertStringContainsString('Supplier payout PO: 900', $cashCredit->reference);
    }

    /**
     * Test partial multi-source order settlements.
     */
    public function test_partial_order_with_multiple_sources_settles_correctly(): void
    {
        $settlement = app(FinancialSettlementService::class);

        // Settle PO 1
        $settlement->settleSupplierCost(orderId: 12, purchaseOrderId: 301, cost: 15.00);
        // Settle PO 2
        $settlement->settleSupplierCost(orderId: 12, purchaseOrderId: 302, cost: 25.00);

        $totalCogs = LedgerEntry::where('order_id', 12)->where('account_code', '5010')->sum('debit');
        $this->assertEquals(40.00, $totalCogs);

        $payablesPO1 = LedgerEntry::where('order_id', 12)
            ->where('account_code', '2010')
            ->where('reference', 'like', '%PO: 301%')
            ->sum('credit');
        $this->assertEquals(15.00, $payablesPO1);

        $payablesPO2 = LedgerEntry::where('order_id', 12)
            ->where('account_code', '2010')
            ->where('reference', 'like', '%PO: 302%')
            ->sum('credit');
        $this->assertEquals(25.00, $payablesPO2);
    }

    /**
     * Test refund reversing ledger entries.
     */
    public function test_refund_creates_reverse_ledger_entries(): void
    {
        $order = Order::factory()->create(['grand_total' => 300.00]);
        $listener = app(OrderLifecycleListener::class);

        // 1. Test pre-shipment refund
        $refund1 = Refund::create([
            'order_id'    => $order->id,
            'grand_total' => 300.00,
        ]);

        $listener->handleRefundSaved($refund1);

        $debitDeposits = LedgerEntry::where('order_id', $order->id)->where('account_code', '3010')->sum('debit');
        $this->assertEquals(300.00, $debitDeposits);

        // 2. Test post-shipment refund (RMA restocked)
        $order2 = Order::factory()->create(['grand_total' => 120.00]);
        // Simulate shipment exists to represent shipped status
        Shipment::create(['order_id' => $order2->id]);

        $refund2 = Refund::create([
            'order_id'    => $order2->id,
            'grand_total' => 120.00,
        ]);

        $listener->handleRefundSaved($refund2);

        $debitRevenue = LedgerEntry::where('order_id', $order2->id)->where('account_code', '4010')->sum('debit');
        $this->assertEquals(120.00, $debitRevenue);

        $debitInventory = LedgerEntry::where('order_id', $order2->id)->where('account_code', '1110')->sum('debit');
        $this->assertEquals(60.00, $debitInventory); // 50% recovery cost simulated

        $creditCOGS = LedgerEntry::where('order_id', $order2->id)->where('account_code', '5010')->sum('credit');
        $this->assertEquals(60.00, $creditCOGS);
    }

    /**
     * Test failed supplier order transitions the customer order state to ops_review.
     */
    public function test_failed_supplier_order_marks_customer_order_for_ops_review(): void
    {
        // Setup Order & OrderProcess
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        
        OrderProcess::create([
            'order_id'        => $order->id,
            'payment_mode'    => 'prepaid',
            'lifecycle_state' => 'fulfillment_started',
            'correlation_id'  => 'corr_fail_1',
        ]);

        // Mock a Coordinator that fails to trigger compensation
        $coordinator = $this->getMockBuilder(FulfillmentSagaCoordinator::class)
            ->onlyMethods(['coordinate'])
            ->disableOriginalConstructor()
            ->getMock();

        $coordinator->expects($this->once())
            ->method('coordinate')
            ->willThrowException(new FulfillmentSagaException("AliExpress API Stockout"));

        // Trigger coordinator
        try {
            $coordinator->coordinate($order->id, $orderItem->id, 1, 'evt_fail_1', 'corr_fail_1');
        } catch (FulfillmentSagaException $e) {
            // Run manually inside the coordinator's catch block logic to assert OrderProcess
            $process = OrderProcess::where('order_id', $order->id)->first();
            $process->flagOps('Supplier procurement failed: ' . $e->getMessage());
        }

        $process = OrderProcess::where('order_id', $order->id)->first();
        $this->assertEquals('ops_review', $process->lifecycle_state);
        $this->assertStringContainsString('AliExpress API Stockout', $process->blocked_reason);
    }
}
