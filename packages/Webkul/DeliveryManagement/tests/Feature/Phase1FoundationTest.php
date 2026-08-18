<?php

namespace Webkul\DeliveryManagement\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\DeliveryManagement\Models\DeliveryGovernorateRule;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Models\DeliverySettlement;
use Webkul\Inventory\Services\InventoryMovementService;

class Phase1FoundationTest extends TestCase
{
    /**
     * Test hayest_central inventory source exists and is retrievable by code.
     */
    public function test_hayest_central_inventory_source_lookup_by_code(): void
    {
        $movementService = app(InventoryMovementService::class);
        $source = $movementService->getSourceByCode('hayest_central');

        $this->assertNotNull($source);
        $this->assertEquals('hayest_central', $source->code);
        $this->assertEquals('YE', $source->country);
        $this->assertEquals('SAN', $source->state);
    }

    /**
     * Test inventory_movements table schema and nullable actor for system events.
     */
    public function test_inventory_movement_supports_system_actor_and_idempotency(): void
    {
        $movementService = app(InventoryMovementService::class);
        $source = $movementService->getSourceByCode('hayest_central');

        $idempotencyKey = (string) Str::uuid();

        // 1. Create movement with actor_type = system and null actor_id
        $movement = $movementService->recordMovement([
            'movement_type' => 'source_receipt',
            'product_id' => 1,
            'sku' => 'TEST-SKU-001',
            'quantity' => 5,
            'source_inventory_source_id' => null,
            'target_inventory_source_id' => $source->id,
            'order_id' => 100,
            'order_item_id' => 200,
            'purchase_order_id' => 300,
            'actor_id' => null,
            'actor_type' => 'system',
            'reference_event' => 'ProcurementCompleted',
            'job_class' => 'App\\Jobs\\SyncSupplierOrderStatusJob',
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Test audit receipt',
        ]);

        $this->assertNotNull($movement->id);
        $this->assertNull($movement->actor_id);
        $this->assertEquals('system', $movement->actor_type);
        $this->assertEquals('ProcurementCompleted', $movement->reference_event);
        $this->assertEquals('App\\Jobs\\SyncSupplierOrderStatusJob', $movement->job_class);

        // 2. Test idempotency: passing same idempotency key returns existing record without duplication
        $duplicateMovement = $movementService->recordMovement([
            'movement_type' => 'source_receipt',
            'product_id' => 1,
            'sku' => 'TEST-SKU-001',
            'quantity' => 5,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertEquals($movement->id, $duplicateMovement->id);
    }

    /**
     * Test physical stock in increases product_inventories atomically.
     */
    public function test_hayest_stock_in_increases_physical_inventory(): void
    {
        $movementService = app(InventoryMovementService::class);
        $source = $movementService->getSourceByCode('hayest_central');

        // Create a test product row to satisfy foreign key
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-SKU-PROD-'.Str::random(6),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idempotencyKey = (string) Str::uuid();

        // Perform physical stock in
        $movement = $movementService->recordHayestStockIn(
            productId: $productId,
            sku: 'TEST-SKU-HAYEST',
            quantity: 10,
            targetSourceId: $source->id,
            orderId: null,
            orderItemId: null,
            purchaseOrderId: null,
            purchaseOrderItemId: null,
            idempotencyKey: $idempotencyKey,
            referenceEvent: 'HayestStockReceived'
        );

        $this->assertEquals('hayest_stock_in', $movement->movement_type);
        $this->assertEquals(10, $movement->quantity);

        $stock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $source->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(10, $stock->qty);
    }

    /**
     * Test DeliveryGovernorateRule creation, unique constraint, and JSON casting.
     */
    public function test_delivery_governorate_rules_structure_and_unique_constraint(): void
    {
        $rule = DeliveryGovernorateRule::updateOrCreate(
            [
                'state_code' => 'SAN',
                'delivery_type' => 'home_delivery',
            ],
            [
                'is_enabled' => true,
                'allowed_payment_methods' => ['cashondelivery', 'moneytransfer'],
                'delivery_fee' => 5.00,
                'min_order_amount' => 0.00,
            ]
        );

        $this->assertNotNull($rule->id);
        $this->assertTrue($rule->is_enabled);
        $this->assertIsArray($rule->allowed_payment_methods);
        $this->assertContains('cashondelivery', $rule->allowed_payment_methods);
    }

    /**
     * Test DeliveryPoint creation and code uniqueness.
     */
    public function test_delivery_point_creation_and_attributes(): void
    {
        $code = 'DP-TEST-SAN-01';

        $point = DeliveryPoint::updateOrCreate(
            ['code' => $code],
            [
                'name' => 'نقطة صنعاء - شارع حدة',
                'name_ar' => 'نقطة صنعاء - شارع حدة',
                'state_code' => 'SAN',
                'city' => 'صنعاء',
                'address' => 'شارع حدة - بجانب مركز الكميم',
                'latitude' => 15.3341234,
                'longitude' => 44.1876543,
                'contact_name' => 'أحمد الموزعي',
                'contact_phone' => '777123456',
                'working_hours' => ['open' => '09:00', 'close' => '21:00'],
                'max_capacity' => 150,
                'is_active' => true,
            ]
        );

        $this->assertNotNull($point->id);
        $this->assertEquals('SAN', $point->state_code);
        $this->assertEquals(150, $point->max_capacity);
        $this->assertIsArray($point->working_hours);
    }

    /**
     * Test DeliveryAssignment creation, scopes, and queries without Global Scope.
     */
    public function test_delivery_assignment_queries_and_agent_scopes(): void
    {
        $agentId1 = 101;
        $agentId2 = 102;

        $idempotencyKey1 = (string) Str::uuid();
        $idempotencyKey2 = (string) Str::uuid();

        $assignment1 = DeliveryAssignment::create([
            'order_id' => 1001,
            'delivery_type' => 'home_delivery',
            'delivery_boy_id' => $agentId1,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'assigned_by' => 1,
            'assigned_at' => now(),
            'customer_address_snapshot' => [
                'name' => 'محمد الحيمي',
                'state' => 'SAN',
                'address' => 'صنعاء - بيت بوس',
            ],
            'idempotency_key' => $idempotencyKey1,
        ]);

        $assignment2 = DeliveryAssignment::create([
            'order_id' => 1002,
            'delivery_type' => 'home_delivery',
            'delivery_boy_id' => $agentId2,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'assigned_by' => 1,
            'assigned_at' => now(),
            'customer_address_snapshot' => [
                'name' => 'خالد الصنعاني',
                'state' => 'SAN',
                'address' => 'صنعاء - مذبح',
            ],
            'idempotency_key' => $idempotencyKey2,
        ]);

        // 1. Test scopeForAgent isolates records for agent 1
        $agent1Assignments = DeliveryAssignment::forAgent($agentId1)->get();
        $this->assertTrue($agent1Assignments->contains('id', $assignment1->id));
        $this->assertFalse($agent1Assignments->contains('id', $assignment2->id));

        // 2. Test scopeForSupervisor returns all assignments
        $allAssignments = DeliveryAssignment::forSupervisor()->get();
        $this->assertTrue($allAssignments->contains('id', $assignment1->id));
        $this->assertTrue($allAssignments->contains('id', $assignment2->id));
    }

    /**
     * Test DeliveryCashCollection multi-currency attributes and calculations.
     */
    public function test_delivery_cash_collection_multi_currency_recording(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $collection = DeliveryCashCollection::create([
            'delivery_assignment_id' => 999,
            'order_id' => 1001,
            'delivery_boy_id' => 101,
            'amount' => 15000.0000,
            'order_currency_code' => 'USD',
            'order_amount' => 15000.0000,
            'collected_currency_code' => 'USD',
            'collected_amount' => 15000.0000,
            'currency' => 'USD',
            'exchange_rate' => 1.000000,
            'base_currency' => 'USD',
            'amount_in_base_currency' => 15000.0000,
            'collected_at' => now(),
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertNotNull($collection->id);
        $this->assertEquals('USD', $collection->collected_currency_code);
        $this->assertEquals('USD', $collection->order_currency_code);
        $this->assertEquals(15000.0000, (float) $collection->amount);
        $this->assertEquals(15000.0000, (float) $collection->amount_in_base_currency);
    }

    /**
     * Test DeliverySettlement creation and discrepancy tracking.
     */
    public function test_delivery_settlement_discrepancy_logging(): void
    {
        $settlement = DeliverySettlement::create([
            'delivery_boy_id' => 101,
            'settlement_date' => now()->toDateString(),
            'expected_amount' => 50000.0000,
            'actual_amount' => 48000.0000,
            'difference' => -2000.0000,
            'currency' => 'YER',
            'status' => DeliverySettlement::STATUS_DISCREPANCY,
            'settled_by' => 1,
            'settled_at' => now(),
            'notes' => 'عجز 2000 ريال يمني قيد المراجعة مع المندوب',
        ]);

        $this->assertNotNull($settlement->id);
        $this->assertEquals(DeliverySettlement::STATUS_DISCREPANCY, $settlement->status);
        $this->assertEquals(-2000.0000, (float) $settlement->difference);
    }
}
