<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\Invoice;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;
use Webkul\Fulfillment\Models\ProcurementSession;
use Webkul\Fulfillment\Models\OrderProcess;
use Webkul\Fulfillment\Models\OrderAllocation;
use Webkul\Fulfillment\Models\LedgerEntry;
use App\Models\AliExpressToken;
use App\Models\AliExpressProductImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ProductionIntegrationAcceptanceTest extends TestCase
{
    // Inherits DatabaseTransactions from TestCase to avoid database state wipe for other tests

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

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
        ProcurementSession::query()->delete();
        OrderAllocation::query()->delete();
        LedgerEntry::query()->delete();
        AliExpressToken::query()->delete();

        AliExpressToken::create([
            'account'                 => 'aliexpress',
            'access_token'            => 'test-token',
            'refresh_token'           => 'test-refresh',
            'access_token_expires_at' => now()->addDays(7),
            'refresh_token_expires_at'=> now()->addDays(30),
        ]);

        Cache::flush();
    }

    /**
     * Test the full end-to-end checkout-to-webhook-to-ledger workflow with correlation id audit trail validation.
     */
    public function test_complete_procurement_integration_acceptance_lifecycle()
    {
        // 1. Create a customer order
        $order = Order::factory()->create([
            'status'              => 'pending',
            'customer_email'      => 'cust@example.com',
            'customer_first_name' => 'Jane',
            'customer_last_name'  => 'Doe',
            'order_currency_code' => 'USD',
            'grand_total'         => 100.00,
        ]);

        \Webkul\Sales\Models\OrderPayment::factory()->create([
            'order_id' => $order->id,
            'method'   => 'stripe',
        ]);

        OrderAddress::factory()->create([
            'order_id'     => $order->id,
            'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
            'first_name'   => 'Jane',
            'last_name'    => 'Doe',
            'address'      => '456 Test Blvd',
            'city'         => 'Riyadh',
            'state'        => 'Riyadh',
            'postcode'     => '11564',
            'country'      => 'SA',
            'phone'        => '0500000000',
        ]);

        // 2. Create product & order item
        $product = \Webkul\Product\Models\Product::create([
            'type'                => 'simple',
            'attribute_family_id' => 1,
            'sku'                 => 'ae-item-prod',
        ]);

        $item = OrderItem::factory()->create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'qty_ordered' => 2,
            'price'       => 50.00,
            'total'       => 100.00,
            'sku'         => $product->sku,
            'name'        => 'AliExpress Product Test',
            'type'        => $product->type,
        ]);

        // Mock product source import
        AliExpressProductImport::create([
            'product_id'             => $product->id,
            'aliexpress_product_id'  => '1234567890',
            'status'                 => 'success',
            'payload_snapshot'       => json_encode([
                'variants' => [
                    [
                        'sku_id' => 'sku_abc_123',
                        'price'  => 15.00,
                    ]
                ]
            ]),
        ]);

        // Seed default supplier account
        \Webkul\Fulfillment\Models\ProviderAccount::firstOrCreate([
            'provider' => 'aliexpress',
            'name'     => 'Main Account',
        ], [
            'status'        => 'ACTIVE',
            'access_token'  => 'test-token',
            'refresh_token' => 'test-refresh',
        ]);

        // Mock AliExpress Client responses before workflow runs
        Http::fake([
            '*' => Http::response([
                'aliexpress_ds_order_create_response' => [
                    'result' => [
                        'is_success' => true,
                        'order_id'   => 9876543210,
                    ]
                ]
            ], 200)
        ]);

        // 3. Confirm payment (invoice paid prepaid)
        $invoice = Invoice::create([
            'order_id'    => $order->id,
            'state'       => 'paid',
            'grand_total' => 100.00,
        ]);

        // Trigger listener manually or via Laravel events
        $listener = app(\Webkul\Fulfillment\Listeners\OrderLifecycleListener::class);
        $listener->handleInvoiceSaved($invoice);

        // 4. Verify PO, allocations, process and session are created
        $po = PurchaseOrder::where('order_id', $order->id)->first();
        $this->assertNotNull($po);
        
        $process = OrderProcess::where('order_id', $order->id)->first();
        $this->assertNotNull($process);
        $correlationId = $process->correlation_id;
        $this->assertNotEmpty($correlationId);

        // Verify correlation_id propagation to Session
        $this->assertNotEmpty($po->idempotency_key);

        $session = ProcurementSession::where('provider_account_id', $po->provider_account_id)->first();
        $this->assertNotNull($session);
        $this->assertEquals($correlationId, $session->correlation_id);
        $this->assertEquals('SUBMITTED', $session->state);

        // 5. Execute PO submission
        $service = app(\Webkul\Fulfillment\Services\FulfillmentService::class);
        $service->executePurchaseOrder($po);

        $po->refresh();
        $session->refresh();

        $this->assertEquals('submitted', $po->state);
        $this->assertEquals('9876543210', $po->external_order_id);
        $this->assertEquals('SUBMITTED', $session->state);

        // 6. Test Webhook status sync E2E
        // Mock AliExpress webhook payload
        $timestamp = time();
        $secret = config('fulfillment.aliexpress.webhook_secret', 'test-signing-key-9922');
        $body = json_encode([
            'event_id'   => 'evt_test_123',
            'event_type' => 'ORDER_STATUS_CHANGED',
            'order_id'   => '9876543210',
            'status'     => 'wait_receive', // wait_receive maps to SHIPPED
            'tracking'   => [
                'number'  => 'TRK-XYZ-99',
                'company' => 'DHL Express',
            ]
        ]);
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        $response = $this->withHeaders([
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Event-ID'  => 'evt_test_123',
            'X-Event-Type'=> 'ORDER_STATUS_CHANGED',
        ])->postJson(route('fulfillment.webhook', ['provider' => 'aliexpress']), [
            'event_id'   => 'evt_test_123',
            'event_type' => 'ORDER_STATUS_CHANGED',
            'order_id'   => '9876543210',
            'status'     => 'wait_receive',
            'tracking'   => [
                'number'  => 'TRK-XYZ-99',
                'company' => 'DHL Express',
            ]
        ]);

        $response->assertStatus(200);

        // Execute scheduled tasks manually to process outbox/inbox
        app(\Webkul\Fulfillment\Services\Application\OutboxEventProcessor::class)->processPending();

        $po->refresh();
        $session->refresh();

        $this->assertEquals('shipped', $po->state);
        $this->assertEquals('TRK-XYZ-99', $po->tracking_number);
        $this->assertEquals('SHIPPED', $session->state);

        // Verify dynamic payment gateway commission matching config
        $commissionRate = config('fulfillment.commission_rates.stripe');
        $expectedCommission = 100.00 * $commissionRate;

        // Check commission ledger entries
        $commissionEntry = LedgerEntry::where('account_code', '5030')->first();
        $this->assertNotNull($commissionEntry);
        $this->assertEqualsWithDelta($expectedCommission, (float) $commissionEntry->debit, 0.0001);

        // Verify Ledger is balanced
        $totalDebit = LedgerEntry::sum('debit');
        $totalCredit = LedgerEntry::sum('credit');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertGreaterThan(0, $totalDebit);

        // Verify continuous correlation_id trail on Ledger Entries
        $ledgerEntries = LedgerEntry::all();
        foreach ($ledgerEntries as $entry) {
            $this->assertEquals($correlationId, $entry->correlation_id);
        }
    }
}
