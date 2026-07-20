<?php

namespace Tests\Feature\Fulfillment;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Commands\CreateProcurementSessionCommand;
use Webkul\Fulfillment\Handlers\Procurement\CreateProcurementSessionHandler;

class SnapshotIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        DB::table('order_allocations')->delete();
        DB::table('procurement_sessions')->delete();
        DB::table('purchase_orders')->delete();
    }

    /**
     * Test that once a snapshot is created, catalog changes do not affect session values or financial decisions.
     */
    public function test_snapshots_are_immutable_and_independent_of_catalog_changes(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'price' => 15.00]);

        $snapData = [
            'supplier_product_id' => '100200300',
            'supplier_sku_id'     => 'sku-abc',
            'requested_qty'       => 1,
            'available_qty'       => 1,
            'supplier_cost'       => 10.00,
        ];

        // 1. Create OrderAllocation with a snapshot cost of 10.00
        $allocation = OrderAllocation::create([
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'qty'               => 1,
            'source_type'       => 'supplier',
            'source_code'       => 'aliexpress',
            'state'             => 'reserved',
            'supplier_snapshot' => json_encode($snapData)
        ]);

        // 2. Start ProcurementSession
        $createCommand = new CreateProcurementSessionCommand(
            purchaseOrderId: 999,
            orderAllocationId: $allocation->id,
            correlationId: 'corr-id-snap',
            causationId: 'caus-id-snap'
        );

        $session = app(CreateProcurementSessionHandler::class)->handle($createCommand);

        // Explicitly set policy snapshot & save
        $session->update([
            'policy_snapshot' => ['markup' => 0.05, 'shipping_rule' => 'standard'],
            'supplier_snapshot' => $snapData,
            'price_snapshot' => ['base_price' => 10.00, 'final_price' => 10.50]
        ]);

        $this->assertEquals(10.00, $session->supplier_snapshot['supplier_cost']);
        $this->assertEquals(0.05, $session->policy_snapshot['markup']);

        // 3. Modify base product catalog, allocation database, and config values
        $orderItem->update(['price' => 50.00]); // Catalog price modified
        $allocation->update([
            'supplier_snapshot' => json_encode([
                'supplier_product_id' => '100200300',
                'supplier_sku_id'     => 'sku-abc',
                'requested_qty'       => 1,
                'available_qty'       => 1,
                'supplier_cost'       => 20.00, // Modified database cost
            ])
        ]);

        // 4. Reload session and assert snapshots remain absolutely unmodified
        $session->refresh();
        $this->assertEquals(10.00, $session->supplier_snapshot['supplier_cost']);
        $this->assertEquals(0.05, $session->policy_snapshot['markup']);
        $this->assertEquals(10.50, $session->price_snapshot['final_price']);
    }
}
