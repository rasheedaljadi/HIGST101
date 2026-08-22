<?php

namespace Webkul\Procurement\Tests\Unit;

use Tests\TestCase;
use Webkul\Procurement\Support\AliExpressMoneyNormalizer;

class AliExpressMoneyNormalizerTest extends TestCase
{
    /**
     * Test raw integer cents in shipping_fee_cent.
     */
    public function test_normalizes_integer_cents_correctly(): void
    {
        $option = [
            'service_name' => 'CAINIAO_STANDARD',
            'shipping_fee_cent' => 500,
            'shipping_fee_currency' => 'USD',
        ];

        $res = AliExpressMoneyNormalizer::normalizeFreightOption($option);

        $this->assertTrue($res['is_valid']);
        $this->assertSame(500, $res['normalized_minor']);
        $this->assertSame('5.00', $res['formatted_decimal']);
        $this->assertSame('minor_cents', $res['raw_unit']);
        $this->assertSame('USD', $res['currency']);
    }

    /**
     * Test live AliExpress fixture where shipping_fee_cent contains a decimal string like "5.00".
     */
    public function test_normalizes_decimal_string_in_cent_field_without_100x_error(): void
    {
        $liveFixture = [
            'code' => 'CAINIAO_FULFILLMENT_STD',
            'company' => 'AliExpress Selection Standard',
            'shipping_fee_currency' => 'USD',
            'shipping_fee_cent' => '5.00',
            'shipping_fee_format' => 'US $5.00',
            'tracking' => true,
        ];

        $res = AliExpressMoneyNormalizer::normalizeFreightOption($liveFixture);

        $this->assertTrue($res['is_valid']);
        $this->assertSame(500, $res['normalized_minor']);
        $this->assertSame('5.00', $res['formatted_decimal']);
        $this->assertSame('decimal_major_despite_cent_name', $res['raw_unit']);
        $this->assertSame('USD', $res['currency']);
        $this->assertNull($res['error']);
    }

    /**
     * Test standard decimal field in shipping_fee.
     */
    public function test_normalizes_standard_decimal_fee_correctly(): void
    {
        $option = [
            'service_name' => 'CAINIAO_STANDARD',
            'shipping_fee' => '12.50',
            'currency' => 'USD',
        ];

        $res = AliExpressMoneyNormalizer::normalizeFreightOption($option);

        $this->assertTrue($res['is_valid']);
        $this->assertSame(1250, $res['normalized_minor']);
        $this->assertSame('12.50', $res['formatted_decimal']);
        $this->assertSame('decimal_standard', $res['raw_unit']);
        $this->assertSame('USD', $res['currency']);
    }

    /**
     * Test documented free shipping options.
     */
    public function test_normalizes_free_shipping_correctly(): void
    {
        $option1 = ['is_free' => true, 'currency' => 'USD'];
        $res1 = AliExpressMoneyNormalizer::normalizeFreightOption($option1);

        $this->assertTrue($res1['is_valid']);
        $this->assertSame(0, $res1['normalized_minor']);
        $this->assertSame('0.00', $res1['formatted_decimal']);
        $this->assertSame('boolean_free', $res1['raw_unit']);

        $option2 = ['free_shipping' => true, 'currency' => 'USD'];
        $res2 = AliExpressMoneyNormalizer::normalizeFreightOption($option2);

        $this->assertTrue($res2['is_valid']);
        $this->assertSame(0, $res2['normalized_minor']);
        $this->assertSame('0.00', $res2['formatted_decimal']);
        $this->assertSame('boolean_free', $res2['raw_unit']);
    }

    /**
     * Test conflicting fee values are strictly rejected.
     */
    public function test_rejects_conflicting_shipping_fee_fields(): void
    {
        $option = [
            'shipping_fee_cent' => 500, // 5.00
            'shipping_fee' => '15.00',  // 15.00 (Conflict!)
            'currency' => 'USD',
        ];

        $res = AliExpressMoneyNormalizer::normalizeFreightOption($option);

        $this->assertFalse($res['is_valid']);
        $this->assertStringContainsString('PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS', (string) $res['error']);
    }

    /**
     * Test missing or ambiguous fee fields are strictly rejected.
     */
    public function test_rejects_missing_or_ambiguous_fields(): void
    {
        $option = [
            'service_name' => 'UNKNOWN_CARRIER',
            'currency' => 'USD',
        ];

        $res = AliExpressMoneyNormalizer::normalizeFreightOption($option);

        $this->assertFalse($res['is_valid']);
        $this->assertSame('unknown', $res['raw_unit']);
        $this->assertStringContainsString('PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS', (string) $res['error']);
    }
}
