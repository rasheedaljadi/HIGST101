<?php

namespace Webkul\Procurement\Tests\Feature;

use Tests\TestCase;
use Webkul\Procurement\Models\ExternalPlatformOrder;
use Webkul\Procurement\Models\ProcurementBatch;
use Webkul\Procurement\Models\SupplierPurchaseOrder;

class ProcurementPollingSchedulerFeatureFlagTest extends TestCase
{
    /**
     * 1. When V2 is disabled, command exits early with 0 executions.
     */
    public function test_command_aborts_early_when_procurement_v2_disabled(): void
    {
        config(['procurement.v2_enabled' => false]);
        config(['procurement.polling.enabled' => true]);

        $this->artisan('procurement:poll-aliexpress')
            ->expectsOutputToContain('Procurement V2 is currently disabled')
            ->assertSuccessful();
    }

    /**
     * 2. When V2 is enabled but polling is disabled, command aborts early.
     */
    public function test_command_aborts_early_when_polling_disabled(): void
    {
        config(['procurement.v2_enabled' => true]);
        config(['procurement.polling.enabled' => false]);

        $this->artisan('procurement:poll-aliexpress')
            ->expectsOutputToContain('Procurement polling is currently disabled')
            ->assertSuccessful();
    }

    /**
     * 3. When both V2 and polling are enabled, command executes polling cycle on active orders.
     */
    public function test_command_executes_when_both_v2_and_polling_are_enabled(): void
    {
        config(['procurement.v2_enabled' => true]);
        config(['procurement.polling.enabled' => true]);

        $batch = ProcurementBatch::create([
            'batch_number' => 'BATCH-POLL-001',
            'provider' => 'aliexpress',
            'currency_code' => 'USD',
            'destination_signature' => 'hayest_dropship_ye',
            'state' => 'awaiting_manual_payment',
            'lock_version' => 1,
        ]);

        $spo = SupplierPurchaseOrder::create([
            'batch_id' => $batch->id,
            'purchase_order_number' => 'SPO-POLL-TEST',
            'provider' => 'aliexpress',
            'supplier_store_id' => 'store_poll_1',
            'currency_code' => 'USD',
            'destination_signature' => 'hayest_dropship_ye',
            'state' => 'awaiting_manual_payment',
            'expected_total' => 10.0,
            'payment_state' => 'unpaid',
            'lock_version' => 1,
        ]);

        $platformOrder = ExternalPlatformOrder::create([
            'supplier_purchase_order_id' => $spo->id,
            'provider' => 'aliexpress',
            'supplier_store_id' => 'store_poll_1',
            'external_order_id' => 'AE-POLL-TEST-001',
            'raw_status' => 'WAIT_BUYER_PAY',
            'normalized_status' => ExternalPlatformOrder::STATUS_WAIT_BUYER_PAY,
            'currency_code' => 'USD',
            'last_synced_at' => now(),
            'snapshots' => [],
        ]);

        $this->artisan('procurement:poll-aliexpress')
            ->expectsOutputToContain('Starting AliExpress idempotent polling cycle...')
            ->expectsOutputToContain('Found 1 active external platform orders to poll.')
            ->expectsOutputToContain('Polling cycle completed successfully.')
            ->assertSuccessful();
    }
}
