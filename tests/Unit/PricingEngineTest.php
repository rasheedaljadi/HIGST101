<?php

namespace Tests\Unit;

use App\Enums\SourceDiscountPolicy;
use App\Models\HigestPricingRule;
use App\Services\Pricing\DTO\PricingContext;
use App\Services\Pricing\PricingEngine;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    public function test_percentage_margin_calculation(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test 30%',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'version' => 1,
        ]);

        $context = new PricingContext;
        $result = $engine->calculate(20.00, $rule, $context);

        $this->assertEquals(26.00, $result->sellingPrice);
        $this->assertNull($result->specialPrice);
        $this->assertEquals(6.00, $result->marginAmount);
        $this->assertEquals(30.00, $result->marginPercentage);
    }

    public function test_supplier_discount_transformation_to_higest_special_price(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test 30%',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'version' => 1,
        ]);

        // AliExpress source original list price = $50.00, discounted sale cost = $40.00 (20% source discount)
        // With HIGEST 30% margin:
        // Regular Selling Price = $50.00 * 1.30 = $65.00
        // Special Sale Price   = $40.00 * 1.30 = $52.00
        $context = new PricingContext(
            acquisitionOriginalCost: 50.00,
        );

        $result = $engine->calculate(40.00, $rule, $context);

        $this->assertEquals(65.00, $result->sellingPrice);
        $this->assertEquals(52.00, $result->specialPrice);
        // Profit on effective sale = $52.00 - $40.00 = $12.00 (30% margin preserved!)
        $this->assertEquals(12.00, $result->marginAmount);
        $this->assertEquals(30.00, $result->marginPercentage);
    }

    public function test_fixed_markup_calculation(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test $10 Fixed',
            'scope' => 'global',
            'type' => 'fixed',
            'value' => 10.00,
            'version' => 1,
        ]);

        $context = new PricingContext;
        $result = $engine->calculate(20.00, $rule, $context);

        $this->assertEquals(30.00, $result->sellingPrice);
        $this->assertNull($result->specialPrice);
        $this->assertEquals(10.00, $result->marginAmount);
        $this->assertEquals(50.00, $result->marginPercentage);
    }

    public function test_freight_and_fee_pipeline_stages(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test 20%',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 20.00,
            'version' => 1,
        ]);

        $context = new PricingContext(
            shippingCost: 5.00,
            extraFees: 1.00,
        );

        $result = $engine->calculate(10.00, $rule, $context);

        $this->assertEquals(19.20, $result->sellingPrice);
        $this->assertArrayHasKey('freight_adjustment', $result->breakdown);
        $this->assertArrayHasKey('fee_adjustment', $result->breakdown);
    }

    public function test_zero_acquisition_cost_handling(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test Zero',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'version' => 1,
        ]);

        $result = $engine->calculate(0.00, $rule);

        $this->assertEquals(0.00, $result->sellingPrice);
        $this->assertEquals(0.00, $result->marginAmount);
    }

    public function test_exact_aliexpress_discount_semantics_and_profit_margin(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test 30% Margin',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'version' => 1,
        ]);

        // Original list price = $30.00, Actual acquisition cost paid = $20.00
        // Expected behavior:
        // Margin (30%) is calculated on $20.00 actual paid cost -> Effective sale price = $26.00
        // Crossed-out list price = $30.00 * 1.30 = $39.00
        // Profit amount = $26.00 - $20.00 = $6.00 (exactly 30.0% on actual $20 cost!)
        $context = new PricingContext(
            sourceProvider: 'aliexpress',
            currency: 'USD',
            acquisitionOriginalCost: 30.00,
        );

        $result = $engine->calculate(20.00, $rule, $context);

        $this->assertEquals(39.00, $result->sellingPrice);       // Crossed-out regular price
        $this->assertEquals(26.00, $result->specialPrice);       // Customer active sale price
        $this->assertEquals(6.00, $result->marginAmount);         // Profit amount
        $this->assertEquals(30.00, $result->marginPercentage);    // Profit margin percentage on actual cost
    }

    public function test_source_provider_context_integrity(): void
    {
        $context = new PricingContext(
            sourceProvider: 'aliexpress',
            currency: 'USD',
            acquisitionOriginalCost: 25.00,
        );

        $this->assertEquals('aliexpress', $context->sourceProvider);
        $this->assertEquals('USD', $context->currency);
        $this->assertEquals(25.00, $context->acquisitionOriginalCost);
    }

    public function test_absorb_by_higest_source_discount_policy_semantics(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Test Absorb Discount Rule',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'source_discount_policy' => SourceDiscountPolicy::ABSORB_BY_HIGEST,
            'version' => 1,
        ]);

        // Original list price = $30.00, Actual acquisition cost paid = $20.00
        // Expected behavior for ABSORB_BY_HIGEST:
        // Regular price is calculated directly from actual paid cost ($20.00 * 1.30 = $26.00)
        // specialPrice is NULL (no promotional discount badge shown to customer)
        $context = new PricingContext(
            sourceProvider: 'aliexpress',
            currency: 'USD',
            acquisitionOriginalCost: 30.00,
        );

        $result = $engine->calculate(20.00, $rule, $context);

        $this->assertEquals(26.00, $result->sellingPrice);
        $this->assertNull($result->specialPrice);
        $this->assertEquals(6.00, $result->marginAmount);
        $this->assertEquals(30.00, $result->marginPercentage);
    }

    public function test_policy_switching_recalculation_and_rule_versioning(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Dynamic Rule',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'source_discount_policy' => SourceDiscountPolicy::PASS_TO_CUSTOMER,
            'version' => 1,
        ]);

        $context = new PricingContext(
            sourceProvider: 'aliexpress',
            currency: 'USD',
            acquisitionOriginalCost: 30.00,
        );

        // Phase 1: PASS_TO_CUSTOMER
        $passResult = $engine->calculate(20.00, $rule, $context);
        $this->assertEquals(39.00, $passResult->sellingPrice);
        $this->assertEquals(26.00, $passResult->specialPrice);

        // Phase 2: Switch to ABSORB_BY_HIGEST
        $rule->source_discount_policy = SourceDiscountPolicy::ABSORB_BY_HIGEST;
        $absorbResult = $engine->calculate(20.00, $rule, $context);
        $this->assertEquals(26.00, $absorbResult->sellingPrice);
        $this->assertNull($absorbResult->specialPrice);

        // Acquisition cost paid ($20.00) remains untouched in both passes!
        $this->assertEquals(20.00, $passResult->acquisitionCost);
        $this->assertEquals(20.00, $absorbResult->acquisitionCost);
    }

    public function test_freight_and_fees_with_source_discount_policy(): void
    {
        $engine = new PricingEngine;

        $rule = new HigestPricingRule([
            'name' => 'Shipping & Fees Rule',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 30.00,
            'source_discount_policy' => SourceDiscountPolicy::PASS_TO_CUSTOMER,
            'version' => 1,
        ]);

        // Cost = $20, Shipping = $5, Fee = $2 -> Subtotal = $27
        // Margin 30% -> $27 * 1.30 = $35.10 (effective customer price)
        // Original List = $30 + $5 + $2 = $37 -> Margin 30% -> $37 * 1.30 = $48.10 (regular list price)
        $context = new PricingContext(
            sourceProvider: 'aliexpress',
            currency: 'USD',
            acquisitionOriginalCost: 30.00,
            shippingCost: 5.00,
            extraFees: 2.00,
        );

        $result = $engine->calculate(20.00, $rule, $context);

        $this->assertEquals(48.10, $result->sellingPrice);
        $this->assertEquals(35.10, $result->specialPrice);
        // Profit markup on raw cost = $35.10 - $20 (cost) = $15.10 ($8.10 margin + $7 shipping/fees)
        $this->assertEquals(15.10, $result->marginAmount);
    }

    public function test_pricing_rule_crud_and_recalculation_integration(): void
    {
        $rule = HigestPricingRule::create([
            'name' => 'Integration Store Test Rule',
            'scope' => 'global',
            'type' => 'percentage',
            'value' => 35.00,
            'source_discount_policy' => SourceDiscountPolicy::PASS_TO_CUSTOMER,
            'priority' => 10,
            'status' => true,
        ]);

        $this->assertNotNull($rule->id);
        $this->assertEquals(SourceDiscountPolicy::PASS_TO_CUSTOMER, $rule->source_discount_policy);
        $this->assertEquals(1, $rule->version);

        // Update policy
        $rule->update([
            'source_discount_policy' => SourceDiscountPolicy::ABSORB_BY_HIGEST,
        ]);

        $rule->refresh();
        $this->assertEquals(SourceDiscountPolicy::ABSORB_BY_HIGEST, $rule->source_discount_policy);
        $this->assertEquals(2, $rule->version);

        $rule->delete();
    }
}
