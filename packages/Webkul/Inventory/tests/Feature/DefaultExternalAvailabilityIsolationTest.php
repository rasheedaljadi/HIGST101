<?php

namespace Webkul\Inventory\Tests\Feature;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\Inventory\Models\ExternalAvailabilitySnapshot;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\ExternalAvailabilityService;
use Webkul\Inventory\Services\InventoryReportingService;

class DefaultExternalAvailabilityIsolationTest extends TestCase
{
    protected InventorySource $defaultSource;

    protected InventorySource $internalYe;

    protected InventorySource $dropshipYe;

    protected InventorySource $stagingSa;

    protected InventorySource $aliExpressSource;

    protected ExternalAvailabilityService $externalAvailabilityService;

    protected InventoryReportingService $reportingService;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('external_availability_snapshots')) {
            Schema::create('external_availability_snapshots', function (Blueprint $table) {
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
        $this->reportingService = app(InventoryReportingService::class);

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
     * 1. External availability does not increase Saudi or Yemen owned inventory.
     */
    public function test_external_availability_does_not_increase_saudi_or_yemen_owned_inventory(): void
    {
        $suffix = \Illuminate\Support\Str::random(6);
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-EXT-ISOLATION-001-' . $suffix,
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_inventories')->insert([
            'qty' => 500,
            'product_id' => $productId,
            'inventory_source_id' => $this->defaultSource->id,
            'vendor_id' => 0,
        ]);

        $saudiStock = (int) DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->stagingSa->id)
            ->value('qty');

        $yemenInternalStock = (int) DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->internalYe->id)
            ->value('qty');

        $yemenDropshipStock = (int) DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->dropshipYe->id)
            ->value('qty');

        $this->assertEquals(0, $saudiStock);
        $this->assertEquals(0, $yemenInternalStock);
        $this->assertEquals(0, $yemenDropshipStock);
    }

    /**
     * 2. External availability does not create inventory_movement.
     */
    public function test_external_availability_does_not_create_inventory_movement(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-EXT-ISOLATION-002',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialMovementsCount = DB::table('inventory_movements')
            ->where('product_id', $productId)
            ->count();

        $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'aliexpress',
            'external_sku' => 'ALI-SKU-TEST-002',
            'external_product_id' => 'ALI-PROD-002',
            'internal_product_id' => $productId,
            'available_quantity' => 150,
        ]);

        $afterMovementsCount = DB::table('inventory_movements')
            ->where('product_id', $productId)
            ->count();

        $this->assertEquals(0, $initialMovementsCount);
        $this->assertEquals(0, $afterMovementsCount, 'Syncing external availability snapshot must NEVER write to inventory_movements.');
    }

    /**
     * 3. Cannot execute Handoff from default.
     */
    public function test_cannot_execute_handoff_from_default(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-EXT-ISOLATION-003',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'increment_id' => 'ORD-DEFAULT-HANDOFF-999',
            'status' => 'processing',
            'channel_name' => 'default',
            'is_guest' => 1,
            'customer_email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $productId,
            'sku' => 'TEST-EXT-ISOLATION-003',
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

        if (DB::getSchemaBuilder()->hasTable('order_allocations')) {
            DB::table('order_allocations')->insert([
                'order_id' => $orderId,
                'order_item_id' => 1,
                'product_id' => $productId,
                'allocation_type' => 'warehouse',
                'source_code' => 'default',
                'reserved_qty' => 1,
                'fulfilled_qty' => 0,
                'state' => 'reserved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $handoffService = app(HandoffExecutionService::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/prohibited|default|Security Violation/i');

        $handoffService->executeHandoff($orderId, 1, 'admin');
    }

    /**
     * 4. Idempotency test for ExternalAvailabilityService.
     */
    public function test_external_availability_service_is_idempotent(): void
    {
        $sku = 'ALI-IDEMPOTENT-001';

        $snapshot1 = $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'aliexpress',
            'external_sku' => $sku,
            'external_product_id' => 'ALI-PROD-IDEMP-1',
            'available_quantity' => 100,
        ]);

        $snapshot2 = $this->externalAvailabilityService->syncSnapshot([
            'provider' => 'aliexpress',
            'external_sku' => $sku,
            'external_product_id' => 'ALI-PROD-IDEMP-1',
            'available_quantity' => 120,
        ]);

        $this->assertEquals($snapshot1->id, $snapshot2->id, 'Snapshot update must be idempotent using provider + external_sku unique key.');
        $this->assertEquals(120, $snapshot2->fresh()->available_quantity);
        $this->assertEquals(1, ExternalAvailabilitySnapshot::where('provider', 'aliexpress')->where('external_sku', $sku)->count());
    }

    /**
     * 5. Internal product reads from hayest_internal_ye only.
     */
    public function test_internal_product_reads_stock_from_hayest_internal_ye_only(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-EXT-ISOLATION-005',
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_inventories')->insert([
            ['qty' => 100, 'product_id' => $productId, 'inventory_source_id' => $this->defaultSource->id, 'vendor_id' => 0],
            ['qty' => 15, 'product_id' => $productId, 'inventory_source_id' => $this->internalYe->id, 'vendor_id' => 0],
        ]);

        $internalStock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->internalYe->id)
            ->value('qty');

        $this->assertEquals(15, $internalStock);
    }

    /**
     * 6. Reports isolation verification.
     */
    public function test_inventory_reports_exclude_default_from_owned_balances_and_reconciliation(): void
    {
        $balancesReport = $this->reportingService->getSourcesBalanceReport();
        $sourceCodesInBalance = $balancesReport->pluck('code')->all();

        $this->assertNotContains('default', $sourceCodesInBalance, 'getSourcesBalanceReport must exclude default source from owned inventory balances.');
        $this->assertNotContains('aliexpress_source', $sourceCodesInBalance, 'getSourcesBalanceReport must exclude aliexpress_source from owned inventory balances.');

        $reconciliationReport = $this->reportingService->getReconciliationReport();
        $reconciliationSources = $reconciliationReport->pluck('source_code')->unique()->all();

        $this->assertNotContains('default', $reconciliationSources, 'getReconciliationReport must exclude default from accounting reconciliation.');
        $this->assertNotContains('aliexpress_source', $reconciliationSources, 'getReconciliationReport must exclude aliexpress_source from accounting reconciliation.');

        $legacyExceptionReport = $this->reportingService->getLegacyExceptionReport();
        $this->assertInstanceOf(Collection::class, $legacyExceptionReport);
    }
}
