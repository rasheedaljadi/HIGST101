<?php

namespace Tests\Unit\Inventory;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Admin\Http\Controllers\NotificationController;
use Webkul\Notification\Services\StockNotificationService;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductInventory;
use Webkul\Product\Repositories\ProductRepository;

class StockThresholdNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected StockNotificationService $service;

    protected ProductRepository $productRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = app(ProductRepository::class);
        $this->service = app(StockNotificationService::class);

        // Configure test thresholds
        Config::set('catalog.inventory.stock_options.low_stock_threshold', 5);
        Config::set('catalog.inventory.stock_options.out_of_stock_threshold', 0);
    }

    public function test_it_creates_low_stock_notification_when_stock_reaches_low_threshold()
    {
        // 1. Create simple product
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-LOW-STOCK-'.uniqid(),
        ]);

        // 2. Set inventory to 4 (<= 5 and > 0)
        ProductInventory::create([
            'product_id' => $product->id,
            'inventory_source_id' => 1,
            'qty' => 4,
        ]);

        // 3. Trigger check
        $this->service->checkProductStock($product->id);

        // 4. Assert notification exists
        $notification = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'low_stock')
            ->where('entity_id', $product->id)
            ->where('read', 0)
            ->first();

        $this->assertNotNull($notification, 'Low stock notification should be created.');
        $this->assertStringContainsString('انخفاض مخزون', $notification->title);
        $this->assertStringContainsString($product->sku, $notification->message);
        $this->assertEquals("/admin/catalog/products/edit/{$product->id}", $notification->action_url);
    }

    public function test_it_creates_out_of_stock_notification_when_stock_reaches_zero()
    {
        // 1. Create simple product
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-OUT-STOCK-'.uniqid(),
        ]);

        // 2. Set inventory to 0 (<= 0)
        ProductInventory::create([
            'product_id' => $product->id,
            'inventory_source_id' => 1,
            'qty' => 0,
        ]);

        // 3. Trigger check
        $this->service->checkProductStock($product->id);

        // 4. Assert notification exists
        $notification = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'out_of_stock')
            ->where('entity_id', $product->id)
            ->where('read', 0)
            ->first();

        $this->assertNotNull($notification, 'Out of stock notification should be created.');
        $this->assertStringContainsString('نفاد مخزون', $notification->title);
        $this->assertStringContainsString($product->sku, $notification->message);
    }

    public function test_it_deduplicates_notifications_and_updates_message()
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-DEDUP-'.uniqid(),
        ]);

        ProductInventory::create([
            'product_id' => $product->id,
            'inventory_source_id' => 1,
            'qty' => 3,
        ]);

        // Trigger twice
        $this->service->checkProductStock($product->id);
        $this->service->checkProductStock($product->id);

        $count = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'low_stock')
            ->where('entity_id', $product->id)
            ->where('read', 0)
            ->count();

        $this->assertEquals(1, $count, 'Should not create duplicate unread notifications for same product.');
    }

    public function test_it_resolves_notification_when_stock_is_replenished()
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-REPLENISH-'.uniqid(),
        ]);

        $inv = ProductInventory::create([
            'product_id' => $product->id,
            'inventory_source_id' => 1,
            'qty' => 2,
        ]);

        $this->service->checkProductStock($product->id);

        $this->assertEquals(1, DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'low_stock')
            ->where('entity_id', $product->id)
            ->where('read', 0)
            ->count()
        );

        // Replenish stock to 20
        $inv->update(['qty' => 20]);
        $this->service->checkProductStock($product->id);

        $this->assertEquals(0, DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'low_stock')
            ->where('entity_id', $product->id)
            ->where('read', 0)
            ->count(),
            'Prior notification should be marked read after stock replenishment.'
        );
    }

    public function test_notification_controller_formats_and_redirects_stock_notifications()
    {
        $product = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-CTRL-'.uniqid(),
        ]);

        $id = DB::table('notifications')->insertGetId([
            'type' => 'low_stock',
            'customer_id' => null,
            'title' => 'انخفاض مخزون المنتج',
            'message' => 'انخفض المخزون',
            'action_url' => "/admin/catalog/products/edit/{$product->id}",
            'entity_type' => Product::class,
            'entity_id' => $product->id,
            'order_id' => null,
            'read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(NotificationController::class);
        $res = $controller->getNotifications();

        $this->assertArrayHasKey('search_results', $res);
        $this->assertGreaterThanOrEqual(1, $res['total_unread']);

        // Test view notification redirect
        $redirect = $controller->viewedNotifications($id);
        $this->assertTrue($redirect->isRedirect(route('admin.catalog.products.edit', $product->id)));

        // Assert marked as read
        $notif = DB::table('notifications')->where('id', $id)->first();
        $this->assertEquals(1, $notif->read);
    }

    public function test_it_handles_configurable_product_variants_thresholds()
    {
        // 1. Create parent configurable product
        $parent = Product::factory()->create([
            'type' => 'configurable',
            'sku' => 'TEST-CONFIG-'.uniqid(),
        ]);

        // 2. Create child variant simple product
        $variant = Product::factory()->create([
            'type' => 'simple',
            'sku' => 'TEST-VARIANT-'.uniqid(),
            'parent_id' => $parent->id,
        ]);

        // 3. Set variant stock to 3 (<= 5)
        ProductInventory::create([
            'product_id' => $variant->id,
            'inventory_source_id' => 1,
            'qty' => 3,
        ]);

        // 4. Trigger check on parent configurable product
        $this->service->checkProductStock($parent->id);

        // 5. Assert notification created for variant, with action_url pointing to parent product edit page
        $notification = DB::table('notifications')
            ->whereNull('customer_id')
            ->where('type', 'low_stock')
            ->where('entity_id', $variant->id)
            ->where('read', 0)
            ->first();

        $this->assertNotNull($notification, 'Low stock notification should be created for variant.');
        $this->assertEquals("/admin/catalog/products/edit/{$parent->id}", $notification->action_url);
        $this->assertStringContainsString($variant->sku, $notification->message);
    }
}
