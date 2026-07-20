<?php

namespace App\Services\AliExpress;

use Illuminate\Support\Facades\Log;

/**
 * Queries AliExpress freight (shipping) options for a product via
 * aliexpress.ds.freight.query and normalizes the cheapest tracked option into
 * a small array the importer caches on the product.
 *
 * This is the ONLY place that talks to the freight API. It is called once at
 * import time (and by the resync command) — never while a customer browses or
 * checks out. The storefront carrier reads the cached values instead.
 *
 * Ship-to country and currency default to the store's dropshipping context
 * (SA / store base currency) but are configurable.
 */
class AliExpressFreightService
{
    public function __construct(
        protected AliExpressApiClient $apiClient,
        protected AliExpressOAuthService $oauthService,
    ) {}

    /**
     * Fetch and normalize the shipping option for a product+sku.
     *
     * Returns null when shipping cannot be resolved (no token, API error, or no
     * options) so the caller can store nulls and degrade gracefully.
     *
     * @return array{cost:float, currency:string, min_days:?int, max_days:?int, company:?string, tracking:bool}|null
     */
    public function quote(string $productId, ?string $skuId = null, ?string $country = null, ?string $currency = null): ?array
    {
        $token = $this->oauthService->latestToken();

        if ($token === null || ! $token->isAccessTokenValid()) {
            Log::channel('aliexpress')->warning('Freight quote skipped: no valid token', [
                'product_id' => $productId,
            ]);

            return null;
        }

        $country ??= (string) config('aliexpress.import.ship_to_country', 'SA');
        $currency ??= $this->resolveCurrency();

        $req = [
            'productId' => $productId,
            'shipToCountry' => $country,
            'quantity' => 1,
            'currency' => $currency,
            'language' => 'en_US',
            'locale' => 'en_US',
        ];

        if ($skuId !== null && $skuId !== '') {
            $req['selectedSkuId'] = $skuId;
        }

        $result = $this->apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
            'queryDeliveryReq' => $req,
        ]);

        if ($result['ok'] === false) {
            Log::channel('aliexpress')->warning('Freight query failed', [
                'product_id' => $productId,
                'code' => $result['code'],
                'message' => $result['message'],
            ]);

            return null;
        }

        $body = $result['body']['aliexpress_ds_freight_query_response'] ?? $result['body'];

        $options = data_get($body, 'result.delivery_options.delivery_option_d_t_o', []);

        if (! is_array($options) || $options === []) {
            return null;
        }

        // A single option may come back as an assoc array, not a list.
        if (isset($options['code']) || isset($options['shipping_fee_cent'])) {
            $options = [$options];
        }

        return $this->pickBestOption($options, $currency);
    }

    /**
     * Choose the cheapest option, preferring tracked ones. Returns the
     * normalized shipping array.
     *
     * @param  array<int, array<string, mixed>>  $options
     * @return array{cost:float, currency:string, min_days:?int, max_days:?int, company:?string, tracking:bool}
     */
    protected function pickBestOption(array $options, string $currency): array
    {
        $best = null;
        $bestCost = null;

        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }

            $cost = $this->extractCost($opt);

            if ($best === null || $cost < $bestCost) {
                $best = $opt;
                $bestCost = $cost;
            }
        }

        $best ??= $options[0];

        return [
            'cost' => $this->extractCost($best),
            'currency' => (string) ($best['shipping_fee_currency'] ?? $currency),
            'min_days' => $this->intOrNull($best['min_delivery_days'] ?? null),
            'max_days' => $this->intOrNull($best['max_delivery_days'] ?? ($best['guaranteed_delivery_days'] ?? null)),
            'company' => $this->stringOrNull($best['company'] ?? ($best['code'] ?? null)),
            'tracking' => (bool) ($best['tracking'] ?? false),
        ];
    }

    /**
     * Extract a numeric shipping cost from an option (handles cents, formatted).
     *
     * @param  array<string, mixed>  $opt
     */
    protected function extractCost(array $opt): float
    {
        // Prefer the explicit cent/amount field; AliExpress sends it as a
        // decimal string despite the "_cent" name (e.g. "5.00").
        if (isset($opt['shipping_fee_cent']) && is_numeric($opt['shipping_fee_cent'])) {
            return (float) $opt['shipping_fee_cent'];
        }

        if (isset($opt['shipping_fee_amount']) && is_numeric($opt['shipping_fee_amount'])) {
            return (float) $opt['shipping_fee_amount'];
        }

        // Fallback: parse the formatted string like "US $5.00".
        if (isset($opt['shipping_fee_format']) && preg_match('/([\d.]+)/', (string) $opt['shipping_fee_format'], $m)) {
            return (float) $m[1];
        }

        return 0.0;
    }

    protected function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * The store's base currency (the price/shipping reference), default USD.
     */
    protected function resolveCurrency(): string
    {
        try {
            $base = core()->getBaseCurrencyCode();

            if (is_string($base) && $base !== '') {
                return $base;
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        return (string) config('aliexpress.import.target_currency', 'USD');
    }
}
