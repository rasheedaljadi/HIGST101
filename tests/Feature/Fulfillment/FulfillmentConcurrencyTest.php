<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Product\Models\Product;

class FulfillmentConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        // Clean projections
        DB::table('external_variant_projections')->delete();
    }

    /**
     * Test that concurrent updates maintain the latest version and prevent stale overrides.
     */
    public function test_projection_concurrent_update_keeps_latest_version(): void
    {
        // 1. Create a dummy product and projection record
        $productId = DB::table('products')->insertGetId([
            'type' => 'simple',
            'sku' => 'test-concurrent-sku',
            'attribute_family_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_variant_projections')->insert([
            'product_id' => $productId,
            'variant_product_id' => $productId,
            'provider' => 'aliexpress',
            'external_sku_id' => 'ae_test_sku',
            'external_product_id' => 'ae_prod_1',
            'external_variant_version' => '10',
            'projection_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Simulate Worker A (processing version 12)
        // 3. Simulate Worker B (processing version 11 - stale/replay)
        $listener = app(\Webkul\Fulfillment\Listeners\CatalogProjectionListener::class);

        // Apply Worker A (Version 12) -> should succeed
        $listener->handle('SupplierPriceChanged', [
            'variant_id' => $productId,
            'new_price' => 150.00,
            'external_variant_version' => '12',
            'provider_updated_at' => now()->toIso8601String(),
        ], 'corr-1', 'caus-1');

        $projection = DB::table('external_variant_projections')
            ->where('variant_product_id', $productId)
            ->first();

        $this->assertEquals('12', $projection->external_variant_version);

        // Apply Worker B (Version 11 - stale) -> should be discarded
        $listener->handle('SupplierPriceChanged', [
            'variant_id' => $productId,
            'new_price' => 99.00, // Older price
            'external_variant_version' => '11',
            'provider_updated_at' => now()->subMinute()->toIso8601String(),
        ], 'corr-2', 'caus-2');

        // Check that the projection remains at Version 12, and the price was NOT overridden to 99.00
        $projection = DB::table('external_variant_projections')
            ->where('variant_product_id', $productId)
            ->first();

        $this->assertEquals('12', $projection->external_variant_version);

        $productPrice = DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', function ($q) {
                $q->select('id')->from('attributes')->where('code', 'price')->first();
            })
            ->value('float_value');
        $this->assertEquals(150.00, (float) $productPrice);
    }

    /**
     * Test that projection lock recovers successfully after a failed/crashed worker.
     */
    public function test_projection_lock_recovery_after_worker_failure(): void
    {
        $productId = DB::table('products')->insertGetId([
            'type' => 'simple',
            'sku' => 'test-recovery-sku',
            'attribute_family_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('external_variant_projections')->insert([
            'product_id' => $productId,
            'variant_product_id' => $productId,
            'provider' => 'aliexpress',
            'external_sku_id' => 'ae_test_sku_2',
            'external_product_id' => 'ae_prod_2',
            'external_variant_version' => '10',
            'projection_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simulate Worker A acquiring lock and crashing (transaction rolled back implicitly on exit)
        try {
            DB::transaction(function () use ($productId) {
                DB::table('external_variant_projections')
                    ->where('variant_product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                // Worker A crashes here
                throw new \RuntimeException("Worker A Crashed mid-flight!");
            });
        } catch (\RuntimeException $e) {
            // Worker crash caught
            $this->assertEquals("Worker A Crashed mid-flight!", $e->getMessage());
        }

        // Verify Worker B can immediately acquire lock and process its event
        $listener = app(\Webkul\Fulfillment\Listeners\CatalogProjectionListener::class);

        $listener->handle('SupplierPriceChanged', [
            'variant_id' => $productId,
            'new_price' => 200.00,
            'external_variant_version' => '15',
            'provider_updated_at' => now()->toIso8601String(),
        ], 'corr-3', 'caus-3');

        $projection = DB::table('external_variant_projections')
            ->where('variant_product_id', $productId)
            ->first();

        $this->assertEquals('15', $projection->external_variant_version);

        $productPrice = DB::table('product_attribute_values')
            ->where('product_id', $productId)
            ->where('attribute_id', function ($q) {
                $q->select('id')->from('attributes')->where('code', 'price')->first();
            })
            ->value('float_value');
        $this->assertEquals(200.00, (float) $productPrice);
    }
}
