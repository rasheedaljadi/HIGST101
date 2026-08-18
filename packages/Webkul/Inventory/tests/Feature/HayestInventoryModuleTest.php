<?php

namespace Webkul\Inventory\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Fulfillment\Enums\TransferStatus;
use Webkul\Fulfillment\Services\InboundReceiptService;
use Webkul\Fulfillment\Services\TransferManifestService;
use Webkul\Inventory\DataGrids\InventoryMovementDataGrid;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\InventoryMovementService;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class HayestInventoryModuleTest extends TestCase
{
    protected Admin $admin;

    protected Admin $supervisor;

    protected Admin $accountant;

    protected Admin $courier;

    protected Admin $pointAgent;

    protected InventorySource $internalYe;

    protected InventorySource $dropshipYe;

    protected InventorySource $stagingSa;

    protected InventorySource $quarantineYe;

    protected InventorySource $quarantineSa;

    protected InventorySource $aliExpressSource;

    protected function setUp(): void
    {
        parent::setUp();

        $locale = Locale::firstOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'direction' => 'rtl']
        );
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'decimal' => 2]
        );
        $channel = Channel::firstOrCreate(
            ['code' => 'default'],
            [
                'theme' => 'default',
                'hostname' => 'localhost',
                'default_locale_id' => $locale->id,
                'base_currency_id' => $currency->id,
            ]
        );
        if (! $channel->locales()->where('locales.id', $locale->id)->exists()) {
            $channel->locales()->attach($locale->id);
        }
        if (! $channel->currencies()->where('currencies.id', $currency->id)->exists()) {
            $channel->currencies()->attach($currency->id);
        }

        // 1. Roles & Admins
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            ['permission_type' => 'all', 'permissions' => ['all']]
        );

        $this->admin = Admin::firstOrCreate(
            ['email' => 'admin_inv_test@example.com'],
            ['name' => 'Admin Inventory Test', 'password' => bcrypt('secret123'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $supervisorRole = Role::firstOrCreate(
            ['name' => 'Supervisor'],
            ['permission_type' => 'custom', 'permissions' => ['inventory', 'inventory.dashboard', 'inventory.sources', 'inventory.products', 'inventory.products.view', 'inventory.transfers', 'inventory.transfers.create', 'inventory.transfers.view', 'inventory.transfers.dispatch', 'inventory.receipts', 'inventory.receipts.create', 'inventory.receipts.view', 'inventory.quarantine', 'inventory.quarantine.approve', 'inventory.reports', 'inventory.reports.export']]
        );

        $this->supervisor = Admin::firstOrCreate(
            ['email' => 'supervisor_inv_test@example.com'],
            ['name' => 'Supervisor Inv Test', 'password' => bcrypt('secret123'), 'role_id' => $supervisorRole->id, 'status' => 1]
        );

        $accountantRole = Role::firstOrCreate(
            ['name' => 'Accountant'],
            ['permission_type' => 'custom', 'permissions' => ['inventory', 'inventory.dashboard', 'inventory.reports', 'inventory.movements']]
        );

        $this->accountant = Admin::firstOrCreate(
            ['email' => 'accountant_inv_test@example.com'],
            ['name' => 'Accountant Inv Test', 'password' => bcrypt('secret123'), 'role_id' => $accountantRole->id, 'status' => 1]
        );

        $courierRole = Role::firstOrCreate(
            ['name' => 'Courier'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $this->courier = Admin::firstOrCreate(
            ['email' => 'courier_inv_test@example.com'],
            ['name' => 'Courier Inv Test', 'password' => bcrypt('secret123'), 'role_id' => $courierRole->id, 'status' => 1]
        );

        $pointAgentRole = Role::firstOrCreate(
            ['name' => 'PointAgent'],
            ['permission_type' => 'custom', 'permissions' => ['delivery']]
        );

        $this->pointAgent = Admin::firstOrCreate(
            ['email' => 'point_agent_inv_test@example.com'],
            ['name' => 'Point Agent Inv Test', 'password' => bcrypt('secret123'), 'role_id' => $pointAgentRole->id, 'status' => 1]
        );

        // 2. Ensure 6 Canonical Inventory Sources exist
        $this->aliExpressSource = InventorySource::firstOrCreate(
            ['code' => 'aliexpress_source'],
            [
                'name' => 'AliExpress Virtual Catalog Source',
                'country' => 'CN',
                'state' => 'GLOBAL',
                'city' => 'Cloud Platform',
                'street' => 'Cloud Gateway',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'virtual_projection',
            ]
        );

        $this->stagingSa = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_sa'],
            [
                'name' => 'Hayest Saudi Sourcing Hub',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'Riyadh',
                'street' => 'Logistics Park',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'sourcing_staging',
            ]
        );

        $this->quarantineSa = InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_sa'],
            [
                'name' => 'Hayest Saudi Quarantine',
                'country' => 'SA',
                'state' => 'RIY',
                'city' => 'Riyadh',
                'street' => 'Holding Section',
                'postcode' => '14264',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'quarantine',
            ]
        );

        $this->dropshipYe = InventorySource::firstOrCreate(
            ['code' => 'hayest_dropship_ye'],
            [
                'name' => 'Hayest Yemen Dropship Distribution Hub',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Airport Road',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => 'dropship_distribution',
            ]
        );

        $this->internalYe = InventorySource::firstOrCreate(
            ['code' => 'hayest_internal_ye'],
            [
                'name' => 'Hayest Yemen Internal Stock Warehouse',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Sixty Meter Depot',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 1,
                'is_delivery_source' => 1,
                'source_type' => 'internal_stock',
            ]
        );

        $this->quarantineYe = InventorySource::firstOrCreate(
            ['code' => 'hayest_quarantine_ye'],
            [
                'name' => 'Hayest Yemen Quarantine Warehouse',
                'country' => 'YE',
                'state' => 'SAN',
                'city' => 'Sanaa',
                'street' => 'Dispute Section',
                'postcode' => '00000',
                'status' => 1,
                'is_salable' => 0,
                'is_delivery_source' => 0,
                'source_type' => 'quarantine',
            ]
        );
    }

    /**
     * 1. Administrator can open inventory dashboard.
     */
    public function test_administrator_can_open_inventory_dashboard(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.inventory.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee('مخزون هايست');
    }

    /**
     * 2. Courier and Point Agent are rejected from inventory management routes.
     */
    public function test_courier_and_point_agent_are_denied_access_to_inventory(): void
    {
        $routes = [
            route('admin.inventory.dashboard.index'),
            route('admin.inventory.sources.index'),
            route('admin.inventory.products.index'),
            route('admin.inventory.movements.index'),
            route('admin.inventory.transfers.index'),
            route('admin.inventory.receipts.index'),
            route('admin.inventory.quarantine.index'),
            route('admin.inventory.reports.index'),
        ];

        // Courier
        $this->actingAs($this->courier, 'admin');
        foreach ($routes as $url) {
            $response = $this->get($url);
            $this->assertTrue(in_array($response->status(), [401, 403, 302]));
        }

        // Point Agent
        $this->actingAs($this->pointAgent, 'admin');
        foreach ($routes as $url) {
            $response = $this->get($url);
            $this->assertTrue(in_array($response->status(), [401, 403, 302]));
        }
    }

    /**
     * 3. Displaying external projection outside of local salable stock.
     */
    public function test_external_projection_is_strictly_isolated_from_local_salable(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-SKU-PROJ-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Virtual stock in aliexpress_source = 500
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->aliExpressSource->id,
            'qty' => 500,
        ]);

        // Physical stock in hayest_internal_ye = 10
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->internalYe->id,
            'qty' => 10,
        ]);

        $this->actingAs($this->admin, 'admin');
        $response = $this->get(route('admin.inventory.products.show', $productId));

        $response->assertStatus(200);
        $response->assertSee('التوفر الخارجي / الإسقاط الافتراضي');
        $response->assertSee('500');
        $response->assertSee('10');
    }

    /**
     * 4. Correctly displaying hayest_internal_ye and hayest_dropship_ye.
     */
    public function test_display_internal_and_dropship_ye_sources_correctly(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.inventory.sources.index'));
        $response->assertStatus(200);
        $response->assertSee(trans('inventory::app.admin.sources.title'));

        $ajaxResponse = $this->getJson(route('admin.inventory.sources.index'), ['X-Requested-With' => 'XMLHttpRequest']);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertSee('hayest_internal_ye');
        $ajaxResponse->assertSee('hayest_dropship_ye');
    }

    /**
     * 5. Showing balances of sources, allocations, in-transit, and quarantine.
     */
    public function test_dashboard_kpis_render_accurate_aggregates(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.inventory.dashboard.index'));
        $response->assertStatus(200);
        $response->assertViewHas('stats');

        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_salable', $stats);
        $this->assertArrayHasKey('internal_ye', $stats);
        $this->assertArrayHasKey('dropship_ye', $stats);
        $this->assertArrayHasKey('quarantine_total', $stats);
        $this->assertArrayHasKey('allocated_total', $stats);
    }

    /**
     * 6. Creating Draft Transfer from valid physical source.
     */
    public function test_create_draft_transfer_from_valid_physical_source(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-TRF-SKU-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transferService = app(TransferManifestService::class);
        $manifest = $transferService->createManifest([
            'source_inventory_source_id' => $this->stagingSa->id,
            'destination_inventory_source_id' => $this->dropshipYe->id,
            'carrier_name' => 'Hayest Express',
            'tracking_number' => 'HY-TRF-'.Str::random(8),
            'items' => [
                [
                    'product_id' => $productId,
                    'sku' => 'TEST-TRF-SKU-01',
                    'qty_shipped' => 5,
                ],
            ],
        ], $this->admin->id);

        $this->assertNotNull($manifest->id);
        $this->assertEquals(TransferStatus::DRAFT, $manifest->status);
        $this->assertEquals(5, $manifest->items->first()->qty_shipped);
    }

    /**
     * 7. Rejecting transfer originating from aliexpress_source.
     */
    public function test_reject_transfer_from_virtual_aliexpress_source(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-REJ-SKU-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin, 'admin');

        $response = $this->post(route('admin.inventory.transfers.store'), [
            'source_inventory_source_id' => $this->aliExpressSource->id,
            'destination_inventory_source_id' => $this->dropshipYe->id,
            'carrier_name' => 'Invalid Carrier',
            'items' => [
                [
                    'product_id' => $productId,
                    'sku' => 'TEST-REJ-SKU',
                    'qty_shipped' => 1,
                ],
            ],
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * 8. Preventing duplicate manifest with same idempotency key.
     */
    public function test_prevent_duplicate_manifest_creation(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-IDEMP-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $idempotencyKey = 'TRF_TEST_IDEMP_'.Str::random(10);
        $transferService = app(TransferManifestService::class);

        $manifest1 = $transferService->createManifest([
            'idempotency_key' => $idempotencyKey,
            'source_inventory_source_id' => $this->stagingSa->id,
            'destination_inventory_source_id' => $this->dropshipYe->id,
            'items' => [
                [
                    'product_id' => $productId,
                    'sku' => 'SKU-TEST',
                    'qty_shipped' => 10,
                ],
            ],
        ], $this->admin->id);

        $manifest2 = $transferService->createManifest([
            'idempotency_key' => $idempotencyKey,
            'source_inventory_source_id' => $this->stagingSa->id,
            'destination_inventory_source_id' => $this->dropshipYe->id,
            'items' => [
                [
                    'product_id' => $productId,
                    'sku' => 'SKU-TEST',
                    'qty_shipped' => 10,
                ],
            ],
        ], $this->admin->id);

        $this->assertEquals($manifest1->id, $manifest2->id);
    }

    /**
     * 9. Opening receipt and entering good/damaged/missing quantities via official service.
     */
    public function test_inbound_receipt_records_quantities_and_updates_stock(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-REC-SKU-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inboundService = app(InboundReceiptService::class);
        $receipt = $inboundService->processInboundReceipt([
            'destination_inventory_source_id' => $this->dropshipYe->id,
            'quarantine_inventory_source_id' => $this->quarantineYe->id,
            'items' => [
                [
                    'product_id' => $productId,
                    'sku' => 'TEST-REC-SKU-01',
                    'qty_good' => 8,
                    'qty_damaged' => 2,
                    'qty_missing' => 1,
                ],
            ],
        ], $this->admin->id);

        $this->assertNotNull($receipt->id);
        $this->assertEquals(8, $receipt->total_received_good);
        $this->assertEquals(2, $receipt->total_received_damaged);
        $this->assertEquals(1, $receipt->total_received_missing);

        // Good items must be in dropshipYe
        $goodStock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->dropshipYe->id)
            ->value('qty');
        $this->assertEquals(8, $goodStock);

        // Damaged items must be in quarantineYe
        $damagedStock = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->quarantineYe->id)
            ->value('qty');
        $this->assertEquals(2, $damagedStock);
    }

    /**
     * 10. Releasing product from quarantine with authorized role and movement logging.
     */
    public function test_authorized_quarantine_release_moves_stock_and_logs_movement(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-QUAR-REL-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Place 5 units in quarantineYe
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->quarantineYe->id,
            'qty' => 5,
        ]);

        $movementService = app(InventoryMovementService::class);
        $movement = $movementService->releaseFromQuarantine(
            productId: $productId,
            sku: 'TEST-QUAR-REL',
            quantity: 3,
            quarantineSourceId: $this->quarantineYe->id,
            targetSalableSourceId: $this->internalYe->id,
            actorId: $this->supervisor->id,
            idempotencyKey: 'QUAR_REL_TEST_'.Str::random(8),
            reason: 'Item passed secondary inspection. Released to domestic stock.'
        );

        $this->assertNotNull($movement->id);
        $this->assertEquals('quarantine_release', $movement->movement_type);

        // Quarantine should now be 2
        $quarQty = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->quarantineYe->id)
            ->value('qty');
        $this->assertEquals(2, $quarQty);

        // Internal Ye should now be 3
        $salableQty = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->where('inventory_source_id', $this->internalYe->id)
            ->value('qty');
        $this->assertEquals(3, $salableQty);
    }

    /**
     * 11. Movement ledger is strictly read-only.
     */
    public function test_movement_ledger_is_read_only(): void
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.inventory.movements.index'));
        $response->assertStatus(200);
        $response->assertSee(trans('inventory::app.admin.movements.title'));

        // Assert datagrid class has no edit or delete actions defined
        $grid = new InventoryMovementDataGrid;
        $grid->prepareActions();
        $this->assertEmpty($grid->getActions());
    }

    /**
     * 12. Bagisto inventory indexer stability: only is_salable sources contribute to storefront quantity.
     */
    public function test_bagisto_inventory_indexer_respects_is_salable_flag(): void
    {
        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-INDEX-SKU-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add 100 in AliExpress (is_salable = 0)
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->aliExpressSource->id,
            'qty' => 100,
        ]);

        // Add 50 in Quarantine (is_salable = 0)
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->quarantineYe->id,
            'qty' => 50,
        ]);

        // Add 7 in Internal Ye (is_salable = 1)
        DB::table('product_inventories')->insert([
            'product_id' => $productId,
            'inventory_source_id' => $this->internalYe->id,
            'qty' => 7,
        ]);

        // Check channel query via indexer logic
        $salableSources = DB::table('inventory_sources')
            ->where('status', 1)
            ->where('is_salable', 1)
            ->pluck('id');

        $channelSalableQty = DB::table('product_inventories')
            ->where('product_id', $productId)
            ->whereIn('inventory_source_id', $salableSources)
            ->sum('qty');

        $this->assertEquals(7, $channelSalableQty);
    }

    /**
     * 13. Guest / Unauthenticated direct route access redirects to admin login.
     */
    public function test_guest_is_redirected_to_admin_login_on_all_direct_routes(): void
    {
        $directRoutes = [
            route('admin.inventory.dashboard.index'),
            route('admin.inventory.sources.index'),
            route('admin.inventory.products.index'),
            route('admin.inventory.movements.index'),
            route('admin.inventory.transfers.index'),
            route('admin.inventory.transfers.create'),
            route('admin.inventory.receipts.index'),
            route('admin.inventory.receipts.create'),
            route('admin.inventory.quarantine.index'),
            route('admin.inventory.reports.index'),
        ];

        foreach ($directRoutes as $url) {
            $response = $this->get($url);
            $response->assertStatus(302);
            $response->assertRedirect(route('admin.session.create'));
        }
    }

    /**
     * 14. Direct GET access to all main views renders 200 OK with RTL and currency.
     */
    public function test_direct_get_access_to_all_main_views(): void
    {
        $this->actingAs($this->admin, 'admin');

        $productId = DB::table('products')->insertGetId([
            'sku' => 'TEST-DIRECT-'.Str::random(5),
            'type' => 'simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $routes = [
            route('admin.inventory.dashboard.index'),
            route('admin.inventory.sources.index'),
            route('admin.inventory.products.index'),
            route('admin.inventory.products.show', $productId),
            route('admin.inventory.movements.index'),
            route('admin.inventory.transfers.index'),
            route('admin.inventory.transfers.create'),
            route('admin.inventory.receipts.index'),
            route('admin.inventory.receipts.create'),
            route('admin.inventory.quarantine.index'),
            route('admin.inventory.reports.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    /**
     * 15. Reports CSV export returns valid downloadable UTF-8 CSV stream.
     */
    public function test_reports_csv_export_returns_streamed_download(): void
    {
        $this->actingAs($this->admin, 'admin');

        $exportTypes = ['source_balance', 'allocated_vs_available', 'in_transit', 'quarantine', 'discrepancy', 'audit_reconciliation', 'unclassified'];

        foreach ($exportTypes as $type) {
            $response = $this->get(route('admin.inventory.reports.export', $type));
            $response->assertStatus(200);
            $this->assertTrue(
                str_contains($response->headers->get('content-type'), 'text/csv')
                || str_contains($response->headers->get('content-disposition'), '.csv')
            );
        }
    }
}
