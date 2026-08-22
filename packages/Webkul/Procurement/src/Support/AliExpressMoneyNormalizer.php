<?php

namespace Webkul\Procurement\Support;

class AliExpressMoneyNormalizer
{
    /**
     * Normalize freight fee from AliExpress ds.freight.query option.
     *
     * @param  array<string, mixed>  $option
     * @return array{
     *     raw_amount: mixed,
     *     raw_field: string,
     *     raw_unit: string,
     *     normalized_minor: int,
     *     formatted_decimal: string,
     *     currency: string,
     *     is_valid: bool,
     *     error: ?string
     * }
     */
    public static function normalizeFreightOption(array $option, string $defaultCurrency = 'USD'): array
    {
        $currency = (string) ($option['shipping_fee_currency'] ?? $option['currency'] ?? $defaultCurrency);

        // Check for minor unit fields (cents)
        if (isset($option['shipping_fee_cent']) && is_numeric($option['shipping_fee_cent'])) {
            $rawAmount = $option['shipping_fee_cent'];
            $minor = (int) round((float) $rawAmount);

            return [
                'raw_amount' => $rawAmount,
                'raw_field' => 'shipping_fee_cent',
                'raw_unit' => 'minor_cents',
                'normalized_minor' => $minor,
                'formatted_decimal' => number_format($minor / 100, 2, '.', ''),
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        if (isset($option['amount_cent']) && is_numeric($option['amount_cent'])) {
            $rawAmount = $option['amount_cent'];
            $minor = (int) round((float) $rawAmount);

            return [
                'raw_amount' => $rawAmount,
                'raw_field' => 'amount_cent',
                'raw_unit' => 'minor_cents',
                'normalized_minor' => $minor,
                'formatted_decimal' => number_format($minor / 100, 2, '.', ''),
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        // Check for decimal fields (standard units e.g. 12.50)
        if (isset($option['shipping_fee']) && is_numeric($option['shipping_fee'])) {
            $rawAmount = $option['shipping_fee'];
            $minor = (int) round(((float) $rawAmount) * 100);

            return [
                'raw_amount' => $rawAmount,
                'raw_field' => 'shipping_fee',
                'raw_unit' => 'decimal_standard',
                'normalized_minor' => $minor,
                'formatted_decimal' => number_format($minor / 100, 2, '.', ''),
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        if (isset($option['shipping_fee_amount']) && is_numeric($option['shipping_fee_amount'])) {
            $rawAmount = $option['shipping_fee_amount'];
            $minor = (int) round(((float) $rawAmount) * 100);

            return [
                'raw_amount' => $rawAmount,
                'raw_field' => 'shipping_fee_amount',
                'raw_unit' => 'decimal_standard',
                'normalized_minor' => $minor,
                'formatted_decimal' => number_format($minor / 100, 2, '.', ''),
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        if (isset($option['freight']) && is_numeric($option['freight'])) {
            $rawAmount = $option['freight'];
            $minor = (int) round(((float) $rawAmount) * 100);

            return [
                'raw_amount' => $rawAmount,
                'raw_field' => 'freight',
                'raw_unit' => 'decimal_standard',
                'normalized_minor' => $minor,
                'formatted_decimal' => number_format($minor / 100, 2, '.', ''),
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        // Check if explicitly free shipping
        if (isset($option['is_free']) && $option['is_free'] === true) {
            return [
                'raw_amount' => 0,
                'raw_field' => 'is_free',
                'raw_unit' => 'boolean_free',
                'normalized_minor' => 0,
                'formatted_decimal' => '0.00',
                'currency' => strtoupper($currency),
                'is_valid' => true,
                'error' => null,
            ];
        }

        return [
            'raw_amount' => null,
            'raw_field' => 'none',
            'raw_unit' => 'unknown',
            'normalized_minor' => 0,
            'formatted_decimal' => '0.00',
            'currency' => strtoupper($currency),
            'is_valid' => false,
            'error' => 'PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS: No valid numeric shipping fee field detected in delivery option.',
        ];
    }
}
