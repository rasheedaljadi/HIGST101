<?php

namespace Tests\Unit;

use App\Enums\PricingTrigger;
use App\Models\HigestCalculatedPriceHistory;
use App\Models\HigestPricingRule;
use App\Models\HigestProductPriceOverride;
use App\Models\HigestSourceOffer;
use App\Services\Pricing\CatalogPriceWriter;
use App\Services\Pricing\DTO\PricingCalculationResult;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\Product;

class PriceOverrideTest extends TestCase
{
    use DatabaseTransactions;

    protected function createTestProduct(): Product
    {
        $family = AttributeFamily::firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Default', 'status' => 1, 'is_user_defined' => 1]
        );

        return Product::factory()->create([
            'type' => 'simple',
            'attribute_family_id' => $family->id,
        ]);
    }

    public function test_manual_override_bypasses_engine_price_in_catalog(): void
    {
        $product = $this->createTestProduct();

        $priceIndexer = $this->createMock(PriceIndexer::class);
        $flatIndexer = $this->createMock(FlatIndexer::class);
        $writer = new CatalogPriceWriter($priceIndexer, $flatIndexer);

        // Engine theoretical calculation result: regular price = $26.00, special = null
        $calcResult = new PricingCalculationResult(
            acquisitionCost: 20.00,
            acquisitionOriginalCost: 20.00,
            sellingPrice: 26.00,
            specialPrice: null,
            marginAmount: 6.00,
            marginPercentage: 30.00,
            breakdown: ['margin' => 6.00],
        );

        $rule = HigestPricingRule::create([
            'name' => 'Default 30%',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'status' => true,
        ]);

        // Phase 1: Default AUTO mode -> Price should write engine selling price $26.00
        $writer->write(
            variantId: $product->id,
            productId: $product->id,
            result: $calcResult,
            specialPrice: null,
            oldAcquisitionCost: 20.00,
            rule: $rule,
            trigger: PricingTrigger::MANUAL,
        );

        $historyAuto = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest()->first();
        $this->assertEquals(26.00, (float) $historyAuto->new_selling_price);

        // Phase 2: Create Manual Override -> manual_price = $45.00, manual_special_price = $39.00
        HigestProductPriceOverride::create([
            'variant_id' => $product->id,
            'product_id' => $product->id,
            'pricing_mode' => 'MANUAL',
            'manual_price' => 45.00,
            'manual_special_price' => 39.00,
            'override_reason' => 'VIP Customer Premium Pricing',
        ]);

        $writer->write(
            variantId: $product->id,
            productId: $product->id,
            result: $calcResult,
            specialPrice: null,
            oldAcquisitionCost: 20.00,
            rule: $rule,
            trigger: PricingTrigger::MANUAL,
        );

        $historyManual = HigestCalculatedPriceHistory::where('variant_id', $product->id)->latest()->first();
        $this->assertEquals(45.00, (float) $historyManual->new_selling_price);
        $this->assertEquals('manual_override', $historyManual->trigger);
        $this->assertArrayHasKey('manual_override', $historyManual->calculation_breakdown);
        $this->assertEquals(26.00, $historyManual->calculation_breakdown['manual_override']['theoretical_selling_price']);
    }

    public function test_switching_mode_back_to_auto_restores_engine_price(): void
    {
        $product = $this->createTestProduct();

        $priceIndexer = $this->createMock(PriceIndexer::class);
        $flatIndexer = $this->createMock(FlatIndexer::class);
        $writer = new CatalogPriceWriter($priceIndexer, $flatIndexer);

        $calcResult = new PricingCalculationResult(
            acquisitionCost: 20.00,
            acquisitionOriginalCost: 20.00,
            sellingPrice: 26.00,
            specialPrice: null,
            marginAmount: 6.00,
            marginPercentage: 30.00,
            breakdown: [],
        );

        $override = HigestProductPriceOverride::create([
            'variant_id' => $product->id,
            'product_id' => $product->id,
            'pricing_mode' => 'MANUAL',
            'manual_price' => 50.00,
        ]);

        // Write while in MANUAL mode
        $writer->write($product->id, $product->id, $calcResult, null, 20.00, null, 'manual');
        $this->assertEquals(50.00, (float) HigestCalculatedPriceHistory::orderByDesc('id')->first()->new_selling_price);

        // Switch back to AUTO mode
        $override->update(['pricing_mode' => 'AUTO']);

        // Re-write price
        $writer->write($product->id, $product->id, $calcResult, null, 20.00, null, 'sync');
        $this->assertEquals(26.00, (float) HigestCalculatedPriceHistory::orderByDesc('id')->first()->new_selling_price);
    }

    public function test_acquisition_cost_accounting_remains_untouched_during_manual_override(): void
    {
        $product = $this->createTestProduct();

        $offer = HigestSourceOffer::create([
            'variant_id' => $product->id,
            'product_id' => $product->id,
            'source_provider' => 'aliexpress',
            'source_sku_id' => 'ALI-SKU-999',
            'acquisition_cost' => 15.00,
            'acquisition_original_cost' => 20.00,
            'source_currency' => 'USD',
            'captured_at' => now(),
        ]);

        HigestProductPriceOverride::create([
            'variant_id' => $product->id,
            'product_id' => $product->id,
            'pricing_mode' => 'MANUAL',
            'manual_price' => 88.00,
        ]);

        $this->assertEquals(15.00, (float) $offer->fresh()->acquisition_cost);
    }
}
