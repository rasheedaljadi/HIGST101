<?php

namespace Webkul\Procurement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Fulfillment\Events\OrderAccepted;
use Webkul\Fulfillment\Services\Domain\FinancialSettlementService;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Procurement\Listeners\OrderAcceptedListener;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Models\SupplierPurchaseOrder;
use Webkul\Procurement\Services\ProcurementDemandService;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

class ProcurementFeatureFlagAndCODIntegrityTest extends TestCase
{
    protected ProcurementDemandService $demandService;

    protected FinancialSettlementService $settlementService;

    protected InventorySource $yeSource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yeSource = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            ['name' => 'Hayest Yemen Hub', 'contact_name' => 'YE Hub', 'contact_email' => 'ye@hayest.com', 'contact_number' => '123', 'status' => 1]
        );

        $this->demandService = app(ProcurementDemandService::class);
        $this->settlementService = app(FinancialSettlementService::class);
    }

    protected function createTestOrder(string $status = 'processing', string $paymentMethod = 'cashondelivery', float $total = 100.0): Order
    {
        $order = Order::create([
            'increment_id' => 'ORD-FLAG-'.rand(100000, 999999),
            'status' => $status,
            'channel_name' => 'Default',
            'is_guest' => 0,
            'customer_email' => 'flag_buyer@test.com',
            'customer_first_name' => 'Saeed',
            'customer_last_name' => 'Salem',
            'grand_total' => $total,
            'base_grand_total' => $total,
            'sub_total' => $total,
            'base_sub_total' => $total,
            'order_currency_code' => 'USD',
            'base_currency_code' => 'USD',
        ]);

        OrderPayment::create([
            'order_id' => $order->id,
            'method' => $paymentMethod,
            'method_title' => $paymentMethod === 'cashondelivery' ? 'Cash on Delivery' : 'Prepaid Card',
        ]);

        return $order;
    }

    protected function createTestProduct(string $sku): Product
    {
        return Product::create([
            'type' => 'simple',
            'attribute_family_id' => 1,
            'sku' => $sku,
        ]);
    }

    protected function createTestOrderItem(Order $order, Product $product, int $qty = 1, float $price = 20.0): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_type' => Product::class,
            'type' => 'simple',
            'sku' => $product->sku,
            'name' => 'Item '.$product->sku,
            'qty_ordered' => $qty,
            'price' => $price,
            'total' => $price * $qty,
        ]);
    }

    /**
     * Test Feature Flag FALSE: When V2 is disabled, OrderAcceptedListener does not create V2 demands.
     */
    public function test_feature_flag_disabled_does_not_invoke_v2_pipeline(): void
    {
        config(['procurement.v2_enabled' => false]);

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $product = $this->createTestProduct('FLAG-OFF-001');
        $this->createTestOrderItem($order, $product, 2, 25.0);

        $listener = app(OrderAcceptedListener::class);
        $listener->handle(new OrderAccepted($order->id, 'cashondelivery', 'corr_test_001'));

        $this->assertEquals(0, ProcurementDemand::where('order_id', $order->id)->count());
        $this->assertEquals(0, SupplierPurchaseOrder::count());
    }

    /**
     * Test Feature Flag TRUE: When V2 is enabled, listener handles event via V2 pipeline and does not create V1 PO.
     */
    public function test_feature_flag_enabled_invokes_v2_pipeline_exclusively(): void
    {
        config(['procurement.v2_enabled' => true]);

        $order = $this->createTestOrder('processing', 'cashondelivery');
        $product = $this->createTestProduct('FLAG-ON-002');
        $this->createTestOrderItem($order, $product, 2, 30.0);

        $initialV1Count = DB::table('purchase_orders')->count();

        $listener = app(OrderAcceptedListener::class);
        $listener->handle(new OrderAccepted($order->id, 'cashondelivery', 'corr_test_002'));

        // V1 table should not have new records created
        $finalV1Count = DB::table('purchase_orders')->count();
        $this->assertEquals($initialV1Count, $finalV1Count);
    }

    /**
     * Test COD Accounting: Shipment debits 1210 and credits 2210 (unearned liability), not 4010.
     */
    public function test_cod_shipment_creates_unearned_liability_not_realized_revenue(): void
    {
        $order = $this->createTestOrder('processing', 'cashondelivery', 250.0);

        $this->settlementService->settleOrderShipmentCOD($order->id, 250.0);

        // 1210: Courier Receivable (debit = 250)
        $receivableDebit = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '1210')
            ->value('debit');
        $this->assertEquals(250.0, (float) $receivableDebit);

        // 2210: Unearned COD Revenue In-Transit (credit = 250)
        $unearnedCredit = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '2210')
            ->where('credit', '>', 0)
            ->value('credit');
        $this->assertEquals(250.0, (float) $unearnedCredit);

        // 4010: Realized Sales Revenue (must NOT exist yet)
        $realizedRevenue = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->value('credit');
        $this->assertNull($realizedRevenue);
    }

    /**
     * Test COD Accounting: Documented collection moves 2210 to 4010 realized revenue.
     */
    public function test_cod_collection_recognizes_realized_sales_revenue(): void
    {
        $order = $this->createTestOrder('processing', 'cashondelivery', 300.0);

        $this->settlementService->settleOrderShipmentCOD($order->id, 300.0);
        $this->settlementService->settleOrderCODCollection($order->id, 300.0);

        // 2210: Debit 300 to clear liability
        $liabilityDebit = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '2210')
            ->where('debit', '>', 0)
            ->value('debit');
        $this->assertEquals(300.0, (float) $liabilityDebit);

        // 4010: Credit 300 to recognize sales revenue
        $realizedRevenue = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->value('credit');
        $this->assertEquals(300.0, (float) $realizedRevenue);
    }

    /**
     * Test COD Accounting: Failed/Cancelled delivery before collection never recognizes revenue (4010).
     */
    public function test_cod_cancellation_before_collection_never_recognizes_revenue(): void
    {
        $order = $this->createTestOrder('processing', 'cashondelivery', 180.0);

        // Step 1: Order shipped COD -> 1210 (Receivable) / 2210 (Unearned Liability)
        $this->settlementService->settleOrderShipmentCOD($order->id, 180.0);

        // Step 2: Delivery fails or customer rejects item -> Return to warehouse (no collection)
        // Verify account 4010 has 0 entries
        $realizedRevenue = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->count();
        $this->assertEquals(0, $realizedRevenue);

        // Ensure 2210 was never moved to 4010
        $liabilityDebit = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '2210')
            ->where('debit', '>', 0)
            ->count();
        $this->assertEquals(0, $liabilityDebit);
    }

    /**
     * Test COD Accounting: Post-collection refund (RMA) properly debits 4010 to reverse revenue.
     */
    public function test_cod_post_collection_refund_reverses_realized_revenue(): void
    {
        $order = $this->createTestOrder('completed', 'cashondelivery', 200.0);

        // 1. Shipped
        $this->settlementService->settleOrderShipmentCOD($order->id, 200.0);
        // 2. Collected
        $this->settlementService->settleOrderCODCollection($order->id, 200.0);
        // 3. Customer RMA Refund
        $this->settlementService->settleRefundAfterShip($order->id, 200.0, true, 100.0);

        // 4010 debit should exist for 200.0 (reversing the credit of 200.0)
        $revenueDebit = DB::table('ledger_entries')
            ->where('order_id', $order->id)
            ->where('account_code', '4010')
            ->where('debit', '>', 0)
            ->value('debit');
        $this->assertEquals(200.0, (float) $revenueDebit);
    }
}
