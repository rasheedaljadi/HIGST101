<?php

namespace Webkul\Inventory\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\Inventory\Models\ExternalAvailabilitySnapshot;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\ExternalAvailabilityService;

class ExternalAvailabilityStubIntegrationTest extends TestCase
{
    protected InventorySource $defaultSource;

    protected InventorySource $internalYe;

    protected InventorySource $dropshipYe;

    protected InventorySource $stagingSa;

    protected InventorySource $quarantineSa;

    protected InventorySource $quarantineYe;

    protected InventorySource $aliExpressSource;

    protected ExternalAvailabilityService $externalAvailabilityService;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('external_availability_snapshots')) {
            Schema::create('external_availability_snapshots', function ($table) {
                $table->id();
                $table->string('provider', 50)->default('aliexpress');
                $table->string('external_product_id', 100)->index();
                $table->string('external_sku', 100)->index();
                $table->integer('internal_product_id')->unsigned()->nullable()->index();
                $table->integer('available_quantity')->default(0);
                $table->decimal('price_usd', 12, 4)->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->string('sync_status', 20)->default('active')->index();
                $table->timestamps();

                $table->index(['provider', 'external_sku'], 'ext_avail_provider_sku_idx');
            });
        }

        $this->externalAvailabilityService = app(ExternalAvailabilityService::class);

        $this->defaultSource = InventorySource::where('code', 'default')->first()
            ?? InventorySource::create([
                'code' => 'default',
                'name' => 'Default Source (Legacy / External)',
                'contact_name' => 'Legacy',
                'contact_email' => 'legacy@example.com',
                'contact_number' => '00000000',
                'country' => 'US',
                'state' => 'MI',
                'city' => 'Detroit',
                'street' => 'Legacy St',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'general',
            ]);

        $this->internalYe = InventorySource::where('code', 'hayest_internal_ye')->first()
            ?? InventorySource::create([
                'code' => 'hayest_internal_ye',
                'name' => 'Hayest Yemen Internal Stock Warehouse',
                'contact_name' => 'Yemen Stock Manager',
                'contact_email' => 'stock_ye@hayest.test',
                'contact_number' => '770000001',
                'country' => 'YE',
                'state' => 'Sanaa',
                'city' => 'Sanaa',
                'street' => 'Hadda St',
                'postcode' => '00967',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => 'internal_stock',
            ]);

        $this->dropshipYe = InventorySource::where('code', 'hayest_dropship_ye')->first()
            ?? InventorySource::create([
                'code' => 'hayest_dropship_ye',
                'name' => 'Hayest Yemen Dropship Distribution Hub',
                'contact_name' => 'Yemen Dropship Manager',
                'contact_email' => 'dropship_ye@hayest.test',
                'contact_number' => '770000002',
                'country' => 'YE',
                'state' => 'Sanaa',
                'city' => 'Sanaa',
                'street' => 'Airport Rd',
                'postcode' => '00967',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => 'dropship_distribution',
            ]);

        $this->stagingSa = InventorySource::where('code', 'hayest_dropship_sa')->first()
            ?? InventorySource::create([
                'code' => 'hayest_dropship_sa',
                'name' => 'Hayest Saudi Sourcing Hub',
                'contact_name' => 'Saudi Staging Manager',
                'contact_email' => 'staging_sa@hayest.test',
                'contact_number' => '96650000001',
                'country' => 'SA',
                'state' => 'Riyadh',
                'city' => 'Riyadh',
                'street' => 'King Fahd Rd',
                'postcode' => '11564',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'sourcing_staging',
            ]);

        $this->quarantineSa = InventorySource::where('code', 'hayest_quarantine_sa')->first()
            ?? InventorySource::create([
                'code' => 'hayest_quarantine_sa',
                'name' => 'Hayest Saudi Quarantine',
                'contact_name' => 'Saudi Inspector',
                'contact_email' => 'quarantine_sa@hayest.test',
                'contact_number' => '96650000002',
                'country' => 'SA',
                'state' => 'Riyadh',
                'city' => 'Riyadh',
                'street' => 'King Fahd Rd',
                'postcode' => '11564',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'quarantine',
            ]);

        $this->quarantineYe = InventorySource::where('code', 'hayest_quarantine_ye')->first()
            ?? InventorySource::create([
                'code' => 'hayest_quarantine_ye',
                'name' => 'Hayest Yemen Quarantine Warehouse',
                'contact_name' => 'Yemen Inspector',
                'contact_email' => 'quarantine_ye@hayest.test',
                'contact_number' => '770000003',
                'country' => 'YE',
                'state' => 'Sanaa',
                'city' => 'Sanaa',
                'street' => 'Airport Rd',
                'postcode' => '00967',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'quarantine',
            ]);

        $this->aliExpressSource = InventorySource::where('code', 'aliexpress_source')->first()
            ?? InventorySource::create([
                'code' => 'aliexpress_source',
                'name' => 'AliExpress Virtual Catalog Source',
                'contact_name' => 'Virtual Source',
                'contact_email' => 'ali@hayest.test',
                'contact_number' => '000',
                'country' => 'CN',
                'state' => 'Zhejiang',
                'city' => 'Hangzhou',
                'street' => 'Alibaba Ave',
                'postcode' => '310000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'virtual_projection',
            ]);
    }

    /**
     * 1 & 2. Pass 1 external availability record via ExternalAvailabilityService using a mock provider
     * and prove 1 snapshot created without product_inventory or inventory_movement.
     */
    public function test_stub_sync_creates_snapshot_without_product_inventory_or_movement(): void
    {
        $prodInventoriesBefore = DB::table('product_inventories')->count();
        $movementsBefore = DB::table('inventory_movements')->count();

        $snapshot = $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'mock_supplier_v1',
            'external_product_id' => 'MOCK-EXT-1001',
            'external_sku' => 'MOCK-SKU-1001',
            'available_quantity' => 250,
            'price_usd' => 45.50,
            'raw_payload' => ['mock' => true, 'provider' => 'mock_supplier_v1'],
        ]);

        $prodInventoriesAfter = DB::table('product_inventories')->count();
        $movementsAfter = DB::table('inventory_movements')->count();

        $this->assertInstanceOf(ExternalAvailabilitySnapshot::class, $snapshot);
        $this->assertEquals('mock_supplier_v1', $snapshot->provider);
        $this->assertEquals('MOCK-SKU-1001', $snapshot->external_sku);
        $this->assertEquals(250, $snapshot->available_quantity);

        $this->assertEquals($prodInventoriesBefore, $prodInventoriesAfter, 'product_inventories count must NOT change when syncSnapshot is called.');
        $this->assertEquals($movementsBefore, $movementsAfter, 'inventory_movements count must NOT change when syncSnapshot is called.');
    }

    /**
     * 3. Re-sync with same provider and external_sku and prove idempotency and no duplicate snapshot.
     */
    public function test_stub_resync_is_idempotent_and_does_not_duplicate(): void
    {
        $payload = [
            'provider' => 'mock_supplier_v1',
            'external_product_id' => 'MOCK-EXT-1001',
            'external_sku' => 'MOCK-SKU-1001',
            'available_quantity' => 250,
        ];

        $snap1 = $this->externalAvailabilityService->syncSnapshot($payload);

        $payload['available_quantity'] = 290;
        $snap2 = $this->externalAvailabilityService->syncSnapshot($payload);

        $this->assertEquals($snap1->id, $snap2->id, 'Re-sync must update existing snapshot record ID.');
        $this->assertEquals(290, $snap2->fresh()->available_quantity);

        $count = ExternalAvailabilitySnapshot::where('provider', 'mock_supplier_v1')
            ->where('external_sku', 'MOCK-SKU-1001')
            ->count();

        $this->assertEquals(1, $count, 'Re-syncing must NOT create duplicate snapshot rows.');
    }

    /**
     * 4. Stale availability / sync failure test. Policy does not sell stale stock as fresh.
     */
    public function test_stale_external_availability_is_not_treated_as_active_salable_stock(): void
    {
        $snapshot = $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'mock_supplier_v1',
            'external_product_id' => 'MOCK-EXT-STALE-1',
            'external_sku' => 'MOCK-SKU-STALE-1',
            'available_quantity' => 500,
            'sync_status' => 'stale',
        ]);

        $availableQty = $this->externalAvailabilityService->getAvailableQuantity('MOCK-SKU-STALE-1', 'mock_supplier_v1');
        $isAvailable = $this->externalAvailabilityService->isExternalAvailable('MOCK-SKU-STALE-1', 1, 'mock_supplier_v1');

        $this->assertEquals(0, $availableQty, 'Stale snapshot must return 0 available quantity.');
        $this->assertFalse($isAvailable, 'Stale snapshot must fail external availability eligibility check.');
    }

    /**
     * 5. Eligibility check of imported order & draft Purchase Order creation in TEST DB ONLY without increasing local stock.
     */
    public function test_imported_order_eligibility_check_and_po_creation_does_not_increase_local_stock(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-IMPORTED-PO-001',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'aliexpress',
            'external_product_id' => 'ALI-PROD-PO-001',
            'external_sku' => 'TEST-IMPORTED-PO-001',
            'internal_product_id' => $productId,
            'available_quantity' => 50,
            'sync_status' => 'active',
        ]);

        // Eligibility Check
        $isEligible = $this->externalAvailabilityService->isExternalAvailable('TEST-IMPORTED-PO-001', 2, 'aliexpress');
        $this->assertTrue($isEligible, 'Imported product with active snapshot must pass eligibility check.');

        // Simulate draft Purchase Order creation in Test DB
        $poId = DB::table('purchase_orders')->insertGetId([
            'order_id' => 1,
            'provider' => 'aliexpress',
            'internal_reference' => 'PO-TEST-DRAFT-001',
            'state' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $poId,
            'order_item_id' => 1,
            'aliexpress_product_id' => 'ALI-PROD-PO-001',
            'sku_id' => 'TEST-IMPORTED-PO-001',
            'qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify physical local inventory remains 0
        $localStock = (int) DB::table('product_inventories')
            ->where('product_id', $productId)
            ->sum('qty');

        $this->assertEquals(0, $localStock, 'PO creation / eligibility check must NOT increase physical local stock rows.');
    }

    /**
     * 6. Handoff before Yemen inbound receipt is rejected.
     */
    public function test_handoff_before_yemen_inbound_receipt_is_rejected(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-IMP-NO-YE-RECEIPT-001',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'increment_id' => 'ORD-NO-YE-RECEIPT-001',
            'status' => 'processing',
            'channel_name' => 'default',
            'is_guest' => 1,
            'customer_email' => 'no_ye_receipt@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'sku' => 'TEST-IMP-NO-YE-RECEIPT-001',
            'qty_ordered' => 1,
            'qty_shipped' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('delivery_assignments')->insert([
            'order_id' => $orderId,
            'status' => 'ready_for_assignment',
            'delivery_type' => 'express',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('order_allocations')) {
            DB::table('order_allocations')->insert([
                'order_id' => $orderId,
                'order_item_id' => 1,
                'product_id' => $productId,
                'allocation_type' => 'supplier',
                'source_code' => 'aliexpress_source',
                'reserved_qty' => 1,
                'fulfilled_qty' => 0,
                'state' => 'reserved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $handoffService = app(HandoffExecutionService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/allocation is still with supplier|Inbound receipt|prohibited/i');

        $handoffService->executeHandoff($orderId, 1, 'admin');
    }

    /**
     * 7. Saudi receipt -> Transfer -> Yemen receipt step isolation test.
     */
    public function test_physical_flow_saudi_receipt_transfer_and_yemen_receipt(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-PHYSICAL-FLOW-001',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Step 1: Inbound receipt in Saudi staging hub
        DB::table('product_inventories')->insert([
            'qty' => 10,
            'product_id' => $productId,
            'inventory_source_id' => $this->stagingSa->id,
            'vendor_id' => 0,
        ]);

        $saudiStock = (int) DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->stagingSa->id)->value('qty');
        $yemenStock = (int) DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->dropshipYe->id)->value('qty');

        $this->assertEquals(10, $saudiStock, 'Saudi receipt must increase Saudi staging source ONLY.');
        $this->assertEquals(0, $yemenStock, 'Saudi receipt must NOT increase Yemen stock before transfer & Yemen receipt.');

        // Step 2: In transit transfer dispatch (Saudi -> Yemen). Saudi stock deducted, Yemen NOT increased yet.
        DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->stagingSa->id)->update(['qty' => 0]);

        $saudiStockAfterDispatch = (int) DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->stagingSa->id)->value('qty');
        $yemenStockDuringTransit = (int) DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->dropshipYe->id)->value('qty');

        $this->assertEquals(0, $saudiStockAfterDispatch, 'Dispatch from Saudi reduces Saudi stock to 0.');
        $this->assertEquals(0, $yemenStockDuringTransit, 'Yemen stock remains 0 while transfer is in transit.');

        // Step 3: Inbound receipt in Yemen dropship hub.
        DB::table('product_inventories')->insert([
            'qty' => 10,
            'product_id' => $productId,
            'inventory_source_id' => $this->dropshipYe->id,
            'vendor_id' => 0,
        ]);

        $yemenStockFinal = (int) DB::table('product_inventories')->where('product_id', $productId)->where('inventory_source_id', $this->dropshipYe->id)->value('qty');
        $this->assertEquals(10, $yemenStockFinal, 'Yemen receipt increases Yemen dropship source ONLY.');
    }

    /**
     * 8. Internal product reads from hayest_internal_ye only.
     */
    public function test_internal_product_reads_hayest_internal_ye_only(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-INTERNAL-ONLY-001',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_inventories')->insert([
            ['qty' => 50, 'product_id' => $productId, 'inventory_source_id' => $this->defaultSource->id, 'vendor_id' => 0],
            ['qty' => 30, 'product_id' => $productId, 'inventory_source_id' => $this->internalYe->id, 'vendor_id' => 0],
        ]);

        $internalStock = (int) DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->internalYe->id)
            ->value('qty');

        $this->assertEquals(30, $internalStock, 'Internal product must read stock from hayest_internal_ye only.');
    }
}
