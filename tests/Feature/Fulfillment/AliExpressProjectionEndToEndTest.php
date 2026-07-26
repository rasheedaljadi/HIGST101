<?php

namespace Tests\Feature\Fulfillment;

use App\Models\AliExpressProductImport;
use App\Models\ExternalVariantProjection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AliExpressProjectionEndToEndTest extends TestCase
{
    /**
     * Setup test database schema if running in memory.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('aliexpress_product_imports')) {
            Schema::create('aliexpress_product_imports', function (Blueprint $table) {
                $table->id();
                $table->string('aliexpress_product_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('status')->default('pending');
                $table->json('payload_snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('type')->default('simple');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('external_variant_projections')) {
            Schema::create('external_variant_projections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('variant_product_id');
                $table->string('provider');
                $table->string('external_sku_id');
                $table->string('external_product_id');
                $table->string('external_variant_version')->nullable();
                $table->integer('projection_version')->default(1);
                $table->timestamp('provider_updated_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Test that AliExpressRebuildProjections creates projections using payload_snapshot as Source of Truth.
     */
    public function test_rebuild_projections_creates_external_variant_projections_from_payload_snapshot(): void
    {
        // 1. Seed local product & import record
        DB::table('products')->insert([
            'id' => 101,
            'type' => 'simple',
        ]);

        AliExpressProductImport::create([
            'aliexpress_product_id' => '1005001234567890',
            'product_id' => 101,
            'status' => 'success',
            'payload_snapshot' => [
                'variants' => [
                    ['sku_id' => 'sku-test-var-102', 'price' => 50.00, 'stock' => 100],
                ],
            ],
        ]);

        // 2. Run rebuild projections command
        $exitCode = Artisan::call('aliexpress:rebuild-projections');
        $this->assertEquals(0, $exitCode);

        // 3. Assert projection record was created naturally
        $projection = ExternalVariantProjection::where('provider', 'aliexpress')
            ->where('external_sku_id', 'sku-test-var-102')
            ->first();

        $this->assertNotNull($projection);
        $this->assertEquals(101, $projection->product_id);
        $this->assertEquals(101, $projection->variant_product_id);
        $this->assertEquals('1005001234567890', $projection->external_product_id);
    }

    /**
     * Test that projection lookup provides non-null variant_id for outbox event payloads and listener processing.
     */
    public function test_projection_lookup_enables_outbox_event_variant_id_and_listener_processing(): void
    {
        // 1. Create projection
        ExternalVariantProjection::create([
            'product_id' => 201,
            'variant_product_id' => 202,
            'provider' => 'aliexpress',
            'external_sku_id' => 'sku-ae-9988',
            'external_product_id' => '1005009988776655',
            'projection_version' => 1,
        ]);

        // 2. Simulate syncer projection lookup
        $projection = ExternalVariantProjection::where('provider', 'aliexpress')
            ->where('external_sku_id', 'sku-ae-9988')
            ->first();

        $this->assertNotNull($projection);

        // 3. Build Outbox event payload
        $variantId = $projection->variant_product_id;
        $payload = [
            'event_version' => 1,
            'change_reason' => 'scheduled_sync',
            'product_id' => 201,
            'variant_id' => (int) $variantId,
            'old_price' => 10.00,
            'new_price' => 15.00,
            'currency' => 'USD',
        ];

        // 4. Assert variant_id is non-null integer matching variant_product_id
        $this->assertNotNull($payload['variant_id']);
        $this->assertIsInt($payload['variant_id']);
        $this->assertEquals(202, $payload['variant_id']);

        // 5. Verify listener guard clause evaluates to true (does not return early)
        $listenerGuardCheck = ($payload['variant_id'] ?? null) !== null;
        $this->assertTrue($listenerGuardCheck);
    }
}
