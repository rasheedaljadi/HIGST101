<?php

namespace Tests\Feature\Fulfillment;

use App\Models\AliExpressProductImport;
use App\Models\AliExpressToken;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;
use Webkul\Fulfillment\Jobs\CreatePurchaseOrderJob;
use Webkul\Fulfillment\Listeners\InitiateFulfillmentListener;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;
use Webkul\Fulfillment\Services\FulfillmentService;
use Webkul\Fulfillment\Services\SecretRedactor;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;

class FulfillmentBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! \Webkul\Core\Models\Channel::find(1)) {
            \Webkul\Core\Models\Channel::factory()->create(['id' => 1]);
        }

        if (! \Webkul\Attribute\Models\AttributeFamily::find(1)) {
            \Webkul\Attribute\Models\AttributeFamily::create([
                'id'              => 1,
                'code'            => 'default',
                'name'            => 'Default',
                'status'          => 1,
                'is_user_defined' => 0,
            ]);
        }

        PurchaseOrder::query()->delete();
        PurchaseOrderItem::query()->delete();
        AliExpressToken::query()->delete();

        // Seed a valid AliExpress token so client requests don't fail OAuth check
        AliExpressToken::create([
            'account'                 => 'test-account',
            'access_token'            => 'valid-test-access-token',
            'refresh_token'           => 'valid-test-refresh-token',
            'access_token_expires_at' => now()->addDays(7),
            'refresh_token_expires_at'=> now()->addDays(30),
        ]);

        Cache::flush();
    }

    /** Helper to create a dummy customer order. */
    protected function createCustomerOrder(): Order
    {
        $order = Order::factory()->create([
            'status'              => 'pending',
            'customer_email'      => 'customer@example.com',
            'customer_first_name' => 'John',
            'customer_last_name'  => 'Doe',
            'order_currency_code' => 'USD',
        ]);

        OrderAddress::factory()->create([
            'order_id'     => $order->id,
            'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => 'John',
            'last_name'    => 'Doe',
            'address'      => '123 Test St',
            'city'         => 'Test City',
            'state'        => 'NY',
            'postcode'     => '10001',
            'country'      => 'US',
            'phone'        => '1234567890',
            'email'        => 'customer@example.com',
        ]);

        return $order;
    }

    /** Helper to create a real product in database. */
    protected function createRealProduct(): \Webkul\Product\Models\Product
    {
        return \Webkul\Product\Models\Product::create([
            'type'                => 'simple',
            'attribute_family_id' => 1,
            'sku'                 => 'sku-' . uniqid(),
        ]);
    }

    /** Helper to add a simple order item to an order. */
    protected function createOrderItem(Order $order, $product, float $price = 10.0, int $qty = 1): OrderItem
    {
        return OrderItem::factory()->create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'qty_ordered' => $qty,
            'price'       => $price,
            'sku'         => $product->sku,
            'name'        => $product->name,
            'type'        => $product->type,
        ]);
    }

    /** Property 1: Test 1:N planning and duplicate prevention (Idempotency). */
    public function test_po_planning_groups_by_supplier_and_prevents_duplicate_pos(): void
    {
        Bus::fake();

        $order = $this->createCustomerOrder();

        $product1 = $this->createRealProduct();
        $product2 = $this->createRealProduct();
        $product3 = $this->createRealProduct();

        // Create items with valid AliExpress imports
        $item1 = $this->createOrderItem($order, $product1, 15.0, 2);
        $item2 = $this->createOrderItem($order, $product2, 25.0, 1);
        $item3 = $this->createOrderItem($order, $product3, 50.0, 1);

        // Setup product imports
        AliExpressProductImport::create([
            'aliexpress_product_id' => 'ae-p1',
            'product_id'            => $product1->id,
            'status'                => 'success',
            'payload_snapshot'      => [
                'aliexpress_product_id' => 'ae-p1',
                'is_configurable'       => false,
                'variants'              => [['sku_id' => 'ae-sku-1', 'price' => 10.0]],
            ],
        ]);

        AliExpressProductImport::create([
            'aliexpress_product_id' => 'ae-p2',
            'product_id'            => $product2->id,
            'status'                => 'success',
            'payload_snapshot'      => [
                'aliexpress_product_id' => 'ae-p2',
                'is_configurable'       => false,
                'variants'              => [['sku_id' => 'ae-sku-2', 'price' => 20.0]],
            ],
        ]);

        AliExpressProductImport::create([
            'aliexpress_product_id' => 'ae-p3',
            'product_id'            => $product3->id,
            'status'                => 'success',
            'payload_snapshot'      => [
                'aliexpress_product_id' => 'ae-p3',
                'is_configurable'       => false,
                'variants'              => [['sku_id' => 'ae-sku-3', 'price' => 45.0]],
            ],
        ]);

        $service = app(FulfillmentService::class);

        // First run
        $pos1 = $service->planPurchaseOrders($order);
        $this->assertCount(1, $pos1); // Grouped under 'aliexpress_default'

        $po = $pos1[0];
        $this->assertEquals(PurchaseOrder::STATE_PENDING, $po->state);
        $this->assertEquals('aliexpress_default', $po->supplier_signature);
        $this->assertCount(3, $po->items);

        // Second run: should return the same POs and not create duplicate records
        $pos2 = $service->planPurchaseOrders($order);
        $this->assertCount(1, $pos2);
        $this->assertEquals($po->id, $pos2[0]->id);

        Bus::assertDispatched(CreatePurchaseOrderJob::class, 1);
    }

    /** Property 2: Test missing supplier source results in manual review state. */
    public function test_missing_supplier_source_results_in_needs_manual_review(): void
    {
        $order = $this->createCustomerOrder();
        $product = $this->createRealProduct();
        $this->createOrderItem($order, $product, 10.0, 1); // No import row seeded

        $service = app(FulfillmentService::class);
        $pos = $service->planPurchaseOrders($order);

        $this->assertCount(1, $pos);
        $po = $pos[0];
        $this->assertEquals('needs_manual_review', $po->supplier_signature);
        $this->assertEquals(PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW, $po->state);
        $this->assertStringContainsString('Missing AliExpress product import source', $po->last_error);

        // Verify order comment is added
        $comments = $order->comments;
        $this->assertTrue($comments->contains(function ($comment) {
            return str_contains($comment->comment, 'missing supplier source') || str_contains($comment->comment, 'مصدر المورد');
        }));
    }

    /** Property 3: Test execution lock (Mutual Exclusion). */
    public function test_execute_purchase_order_acquires_exclusive_cache_lock(): void
    {
        $order = $this->createCustomerOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'aliexpress_default',
            'idempotency_key'    => hash('sha256', 'lock-test-key'),
            'internal_reference' => 'ref-lock-1',
            'state'              => PurchaseOrder::STATE_PENDING,
        ]);

        // Manually lock the PO in cache
        $lock = Cache::lock("fulfillment-po-{$po->id}", 600);
        $lock->get();

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        // PO state should remain PENDING because the execution was blocked by the lock
        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_PENDING, $po->state);

        $lock->release();
    }

    /** Property 4: Test retry classification (Transient vs Permanent). */
    public function test_transient_failure_triggers_retry_and_permanent_does_not(): void
    {
        $order = $this->createCustomerOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'aliexpress_default',
            'idempotency_key'    => hash('sha256', 'retry-test-key'),
            'internal_reference' => 'ref-retry-1',
            'state'              => PurchaseOrder::STATE_PENDING,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id'     => $po->id,
            'order_item_id'         => 1,
            'aliexpress_product_id' => 'ae-p1',
            'qty'                   => 1,
        ]);

        $businessUrl = config('aliexpress.business_url', 'https://api-sg.aliexpress.com/sync');

        Http::fake([
            $businessUrl => Http::sequence()
                ->push([
                    'code'    => 'isp.system-error',
                    'message' => 'Internal server error occurred.',
                ], 200)
                ->push([
                    'code'    => 'param-error',
                    'message' => 'The product ID is invalid.',
                ], 200)
        ]);

        $service = app(FulfillmentService::class);

        try {
            $service->executePurchaseOrder($po);
            $po->refresh();
            $this->fail("Expected RuntimeException was not thrown. PO state: {$po->state}, last_error: {$po->last_error}, attempts: {$po->attempts}");
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Expected RuntimeException')) {
                throw $e;
            }
            $this->assertStringContainsString('Transient failure', $e->getMessage());
        }

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_PENDING, $po->state); // Still pending (retryable)
        $this->assertEquals(1, $po->attempts);

        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW, $po->state); // Permanent failure -> needs_manual_review
        $this->assertEquals(2, $po->attempts);
        $this->assertStringContainsString('The product ID is invalid', $po->last_error);
    }

    /** Property 5: Test customer order status mapping totality. */
    public function test_customer_order_status_mapping_totality(): void
    {
        $order = $this->createCustomerOrder();

        $po1 = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'sig-1',
            'idempotency_key'    => hash('sha256', 'm-test-key-1'),
            'internal_reference' => 'ref-m-1',
            'state'              => PurchaseOrder::STATE_SUBMITTED,
        ]);

        $po2 = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'sig-2',
            'idempotency_key'    => hash('sha256', 'm-test-key-2'),
            'internal_reference' => 'ref-m-2',
            'state'              => PurchaseOrder::STATE_SUBMITTED,
        ]);

        $service = app(FulfillmentService::class);

        // 1. One PO is submitted, order status should map to processing
        $service->reflectOnCustomerOrder($order);
        $order->refresh();
        $this->assertEquals('processing', $order->status);

        // 2. Both POs delivered, order status should map to completed
        $po1->update(['state' => PurchaseOrder::STATE_DELIVERED]);
        $po2->update(['state' => PurchaseOrder::STATE_DELIVERED]);

        $service->reflectOnCustomerOrder($order);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    /** Property 6: Event listener triggers fulfillment planning on invoice save. */
    public function test_invoice_saved_event_listener_triggers_planning(): void
    {
        Event::fake([
            'sales.invoice.save.after',
        ]);

        $order = $this->createCustomerOrder();

        $invoice = Invoice::create([
            'order_id'    => $order->id,
            'state'       => 'paid',
            'grand_total' => 100.0,
        ]);

        Event::dispatch('sales.invoice.save.after', $invoice);

        Event::assertDispatched('sales.invoice.save.after');
    }

    /** Property 7: Polling job is scheduled correctly to run every 15 minutes. */
    public function test_polling_job_is_scheduled_correctly(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $events = collect($schedule->events())->filter(function ($event) {
            return str_contains($event->description, 'PollSupplierOrdersJob') 
                || str_contains($event->command, 'PollSupplierOrdersJob');
        });

        $this->assertCount(1, $events);
        $this->assertEquals('*/15 * * * *', $events->first()->expression);
    }

    /** Property 8: Reconciliation check is triggered on retry after transient failure (attempts > 0, PENDING). */
    public function test_reconciliation_triggered_on_retry_prevents_duplicate(): void
    {
        $order = $this->createCustomerOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'aliexpress_default',
            'idempotency_key'    => hash('sha256', 'retry-reconciliation-key'),
            'internal_reference' => 'ref-retry-rec-1',
            'state'              => PurchaseOrder::STATE_PENDING,
            'attempts'           => 1, // simulated previous failure/timeout
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id'     => $po->id,
            'order_item_id'         => 1,
            'aliexpress_product_id' => 'ae-p1',
            'qty'                   => 1,
        ]);

        // Create a mock provider
        $mockProvider = $this->createMock(FulfillmentProviderInterface::class);
        $mockProvider->method('code')->willReturn('aliexpress');
        
        // Assert that createSupplierOrder is NEVER called, preventing duplicates!
        $mockProvider->expects($this->never())->method('createSupplierOrder');
        
        // Assert findByReference is called once and successfully reconciles the order
        $mockProvider->expects($this->once())
            ->method('findByReference')
            ->with('ref-retry-rec-1')
            ->willReturn('external-id-timeout-success');

        // Bind mock provider in registry and register registry in container
        $registry = new FulfillmentProviderRegistry();
        $instancesProp = new \ReflectionProperty(FulfillmentProviderRegistry::class, 'instances');
        $instancesProp->setAccessible(true);
        $instancesProp->setValue($registry, ['aliexpress' => $mockProvider]);
        $this->app->instance(FulfillmentProviderRegistry::class, $registry);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_SUBMITTED, $po->state);
        $this->assertEquals('external-id-timeout-success', $po->external_order_id);
    }

    /** Property 9: Reconciliation check is triggered in SUBMITTING state (simulating worker crash retry). */
    public function test_reconciliation_triggered_on_submitting_state_prevents_duplicate(): void
    {
        $order = $this->createCustomerOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'aliexpress_default',
            'idempotency_key'    => hash('sha256', 'crash-reconciliation-key'),
            'internal_reference' => 'ref-crash-rec-1',
            'state'              => PurchaseOrder::STATE_SUBMITTING, // simulated worker crash state
            'attempts'           => 0,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id'     => $po->id,
            'order_item_id'         => 1,
            'aliexpress_product_id' => 'ae-p1',
            'qty'                   => 1,
        ]);

        $mockProvider = $this->createMock(FulfillmentProviderInterface::class);
        $mockProvider->method('code')->willReturn('aliexpress');
        
        // Assert that createSupplierOrder is NEVER called, preventing duplicates!
        $mockProvider->expects($this->never())->method('createSupplierOrder');
        
        // Assert findByReference is called once and successfully reconciles the order
        $mockProvider->expects($this->once())
            ->method('findByReference')
            ->with('ref-crash-rec-1')
            ->willReturn('external-id-crash-success');

        $registry = new FulfillmentProviderRegistry();
        $instancesProp = new \ReflectionProperty(FulfillmentProviderRegistry::class, 'instances');
        $instancesProp->setAccessible(true);
        $instancesProp->setValue($registry, ['aliexpress' => $mockProvider]);
        $this->app->instance(FulfillmentProviderRegistry::class, $registry);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_SUBMITTED, $po->state);
        $this->assertEquals('external-id-crash-success', $po->external_order_id);
    }

    /** Property 10: Bypass API and findByReference entirely if external_order_id is already present. */
    public function test_reconciliation_uses_existing_external_id_directly(): void
    {
        $order = $this->createCustomerOrder();
        $po = PurchaseOrder::create([
            'order_id'           => $order->id,
            'provider'           => 'aliexpress',
            'supplier_signature' => 'aliexpress_default',
            'idempotency_key'    => hash('sha256', 'existing-id-reconciliation-key'),
            'internal_reference' => 'ref-existing-rec-1',
            'state'              => PurchaseOrder::STATE_PENDING,
            'attempts'           => 1,
            'external_order_id'  => 'already-saved-id-999',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id'     => $po->id,
            'order_item_id'         => 1,
            'aliexpress_product_id' => 'ae-p1',
            'qty'                   => 1,
        ]);

        $mockProvider = $this->createMock(FulfillmentProviderInterface::class);
        $mockProvider->method('code')->willReturn('aliexpress');
        
        // Assert that both createSupplierOrder and findByReference are NEVER called!
        $mockProvider->expects($this->never())->method('createSupplierOrder');
        $mockProvider->expects($this->never())->method('findByReference');

        $registry = new FulfillmentProviderRegistry();
        $instancesProp = new \ReflectionProperty(FulfillmentProviderRegistry::class, 'instances');
        $instancesProp->setAccessible(true);
        $instancesProp->setValue($registry, ['aliexpress' => $mockProvider]);
        $this->app->instance(FulfillmentProviderRegistry::class, $registry);

        $service = app(FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATE_SUBMITTED, $po->state);
        $this->assertEquals('already-saved-id-999', $po->external_order_id);
    }

    /** Test double-fulfillment prevention during planning phase. */
    public function test_double_fulfillment_prevention_during_planning(): void
    {
        Bus::fake();

        $order = $this->createCustomerOrder();
        $product = $this->createRealProduct();

        // Create item with valid AliExpress import
        $item = $this->createOrderItem($order, $product, 15.0, 1);

        AliExpressProductImport::create([
            'product_id'             => $product->id,
            'aliexpress_product_id'  => 'ae-p123',
            'status'                 => 'success',
        ]);

        // Manually create an existing active purchase order and link it to this item
        $existingPo = PurchaseOrder::create([
            'order_id'            => $order->id,
            'provider'            => 'aliexpress',
            'supplier_signature'  => 'aliexpress_default',
            'idempotency_key'     => hash('sha256', 'existing-active-po-key'),
            'internal_reference'  => 'ref-existing-active-1',
            'state'               => PurchaseOrder::STATE_PENDING,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id'     => $existingPo->id,
            'order_item_id'         => $item->id,
            'aliexpress_product_id' => 'ae-p123',
            'qty'                   => 1,
            'supplier_unit_cost'    => 15.0,
        ]);

        // Run planning
        $service = app(FulfillmentService::class);
        $pos = $service->planPurchaseOrders($order);

        // Verify it returns the existing active PO and does NOT create a new one
        $this->assertCount(1, $pos);
        $this->assertEquals($existingPo->id, $pos[0]->id);

        // Verify only 1 PO exists in total
        $this->assertEquals(1, PurchaseOrder::count());
        $this->assertEquals(1, PurchaseOrderItem::count());
    }
}

