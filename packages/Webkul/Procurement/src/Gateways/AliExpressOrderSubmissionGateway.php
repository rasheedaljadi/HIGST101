<?php

namespace Webkul\Procurement\Gateways;

use App\Models\AliExpressToken;
use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;

class AliExpressOrderSubmissionGateway implements AliExpressOrderGateway
{
    public function __construct(
        protected AliExpressApiClient $apiClient,
        protected AliExpressOAuthService $oauthService
    ) {}

    /**
     * Resolve default Saudi warehouse shipping address configured in Key Management.
     *
     * @return array{contact_person: string, phone_num: string, mobile_no: string, phone_country: string, address: string, city: string, province: string, zip: string, country: string, company_name: string}
     */
    public function resolveWarehouseShippingAddress(?array $override = null): array
    {
        if (! empty($override)) {
            return $this->normalizeAddress($override);
        }

        $warehouse = DB::table('inventory_sources')
            ->where('code', 'default')
            ->first();

        if ($warehouse) {
            $street = $warehouse->street ?? '';
            $contactName = $warehouse->contact_name ?? 'Higesto Warehouse';
            $city = $warehouse->city ?? 'Riyadh';
            $state = $warehouse->state ?? 'Riyadh';
            $country = $warehouse->country ?? 'SA';
            $postcode = $warehouse->postcode ?? '11564';
            $phone = $warehouse->contact_number ?? '0500000000';
            $companyName = $warehouse->name ?? 'Higesto Fulfillment Hub';

            // Auto-translate Arabic Miftah/Aziziyah warehouse names to English for international customs
            if (mb_strpos((string) $street, 'العزيزية') !== false || mb_strpos((string) $street, 'المفتاح') !== false || mb_strpos((string) $contactName, 'المفتاح') !== false) {
                $contactName = 'Al-Miftah Transport Office';
                $street = 'Southern Ring Road, Al-Shabab District, Al-Aziziyah';
                $city = 'Riyadh';
                $state = 'Riyadh';
            }

            return $this->normalizeAddress([
                'contact_person' => $contactName,
                'phone_num' => $phone,
                'mobile_no' => $phone,
                'phone_country' => $this->getPhoneCountry($country),
                'address' => $street ?: 'Southern Ring Road, Al-Aziziyah',
                'city' => $city,
                'province' => $state,
                'zip' => $postcode,
                'country' => strtoupper($country),
                'company_name' => $companyName,
            ]);
        }

        return $this->normalizeAddress([
            'contact_person' => 'Al-Miftah Transport Office',
            'phone_num' => '0500000000',
            'mobile_no' => '0500000000',
            'phone_country' => '966',
            'address' => 'Southern Ring Road, Al-Shabab District, Al-Aziziyah',
            'city' => 'Riyadh',
            'province' => 'Riyadh',
            'zip' => '11564',
            'country' => 'SA',
            'company_name' => 'Higesto Saudi Fulfillment Hub',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function preflight(ExternalOrderDraft $draft): AliExpressOrderPreflight
    {
        $token = $this->resolveToken($draft->providerAccountId);
        if ($token === null || empty($token->access_token)) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: 'SA',
                errorCode: 'IllegalAccessToken',
                errorMessage: 'No valid AliExpress OAuth access token available for preflight.',
                rawDetails: ['token_valid' => false]
            );
        }

        $items = $draft->items;
        if (empty($items)) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: 'SA',
                errorCode: 'EMPTY_ITEMS_DRAFT',
                errorMessage: 'Draft contains zero items.',
                rawDetails: []
            );
        }

        $firstItem = $items[0];
        $productId = (string) ($firstItem['supplier_product_id'] ?? '');
        $skuId = (string) ($firstItem['supplier_sku_id'] ?? '');
        $qty = (int) ($firstItem['qty'] ?? 1);
        $shippingAddress = $this->resolveWarehouseShippingAddress($draft->overrideShippingAddress);
        $country = $shippingAddress['country'] ?: 'SA';

        // 1. Resolve product details & exact sku_attr
        $resolvedSkuAttr = null;
        try {
            $prodRes = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
                'product_id' => $productId,
                'ship_to_country' => $country,
                'target_currency' => $draft->currencyCode ?: 'USD',
                'target_language' => 'en',
            ]);

            if ($prodRes['ok']) {
                $body = $prodRes['body'];
                $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
                $result = $resp['result'] ?? [];
                $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
                foreach ($variants as $v) {
                    if (($v['sku_id'] ?? '') == $skuId && ! empty($v['sku_attr'])) {
                        $resolvedSkuAttr = $v['sku_attr'];
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::channel('aliexpress')->warning('Preflight product resolution warning: '.$e->getMessage());
        }

        // 2. Query live freight options to Saudi Arabia
        try {
            $freightReq = [
                'productId' => $productId,
                'shipToCountry' => $country,
                'quantity' => $qty > 0 ? $qty : 1,
                'currency' => $draft->currencyCode ?: 'USD',
                'language' => 'en_US',
                'locale' => 'en_US',
            ];
            if (! empty($skuId)) {
                $freightReq['selectedSkuId'] = $skuId;
            }

            $freightRes = $this->apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
                'queryDeliveryReq' => $freightReq,
            ]);

            if (! $freightRes['ok']) {
                $errCode = $freightRes['code'] ?? $freightRes['body']['error_response']['code'] ?? 'FREIGHT_QUERY_FAILED';
                $errMsg = $freightRes['message'] ?? $freightRes['body']['error_response']['msg'] ?? 'Freight options query failed';

                return new AliExpressOrderPreflight(
                    isSuccess: false,
                    isDeliverableToDestination: false,
                    destinationCountry: $country,
                    errorCode: (string) $errCode,
                    errorMessage: (string) $errMsg,
                    rawDetails: $freightRes['body'] ?? []
                );
            }

            $body = $freightRes['body']['aliexpress_ds_freight_query_response'] ?? $freightRes['body'] ?? [];
            $options = data_get($body, 'result.delivery_options.delivery_option_d_t_o', []);

            // Smart fallback if variant-specific query had no options
            if ((! is_array($options) || empty($options)) && isset($freightReq['selectedSkuId'])) {
                unset($freightReq['selectedSkuId']);
                $fallbackRes = $this->apiClient->call('aliexpress.ds.freight.query', $token->access_token, [
                    'queryDeliveryReq' => $freightReq,
                ]);
                if ($fallbackRes['ok']) {
                    $fallbackBody = $fallbackRes['body']['aliexpress_ds_freight_query_response'] ?? $fallbackRes['body'] ?? [];
                    $options = data_get($fallbackBody, 'result.delivery_options.delivery_option_d_t_o', []);
                }
            }

            if (! is_array($options) || empty($options)) {
                return new AliExpressOrderPreflight(
                    isSuccess: true,
                    isDeliverableToDestination: false,
                    destinationCountry: $country,
                    resolvedSkuAttr: $resolvedSkuAttr,
                    errorCode: 'NO_SHIPPING_OPTIONS',
                    errorMessage: 'No live shipping options available for destination.',
                    rawDetails: $body
                );
            }

            if (isset($options['code']) || isset($options['shipping_fee_cent'])) {
                $options = [$options];
            }

            $bestOption = $this->pickBestShippingOption($options, $draft->currencyCode);

            return new AliExpressOrderPreflight(
                isSuccess: true,
                isDeliverableToDestination: true,
                destinationCountry: $country,
                shippingServiceName: $bestOption['service_name'] ?? $bestOption['code'] ?? null,
                shippingCost: (float) ($bestOption['cost'] ?? 0.0),
                shippingCurrency: (string) ($bestOption['currency'] ?? 'USD'),
                minDeliveryDays: isset($bestOption['min_days']) ? (int) $bestOption['min_days'] : null,
                maxDeliveryDays: isset($bestOption['max_days']) ? (int) $bestOption['max_days'] : null,
                trackingAvailable: (bool) ($bestOption['tracking'] ?? false),
                resolvedSkuAttr: $resolvedSkuAttr,
                rawDetails: [
                    'available_options_count' => count($options),
                    'selected_option' => $bestOption,
                ]
            );
        } catch (\Throwable $e) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: $country,
                errorCode: 'PREFLIGHT_EXCEPTION',
                errorMessage: $e->getMessage(),
                rawDetails: []
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitUnpaid(ExternalOrderDraft $draft): VerifiedExternalOrderCreated|ExternalOrderSubmissionFailed
    {
        // 1. Guard against synthetic IDs / fallback
        if (empty($draft->correlationKey)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'EMPTY_CORRELATION_KEY',
                errorMessageMasked: 'Order submission requires a valid out_order_id correlation key.',
                providerRequestId: null,
                retryClassification: 'fatal'
            );
        }

        // 2. Resolve access token
        $token = $this->resolveToken($draft->providerAccountId);
        if ($token === null || empty($token->access_token)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'IllegalAccessToken',
                errorMessageMasked: 'No valid AliExpress OAuth access token configured.',
                providerRequestId: null,
                retryClassification: 'non_retryable'
            );
        }

        // 3. Resolve shipping address
        $shippingAddress = $this->resolveWarehouseShippingAddress($draft->overrideShippingAddress);

        // 4. Build product items with live sku_attr resolution
        $productItems = [];
        foreach ($draft->items as $item) {
            $prodId = (string) ($item['supplier_product_id'] ?? '');
            $skuId = (string) ($item['supplier_sku_id'] ?? '');
            $qty = (int) ($item['qty'] ?? 1);
            $skuAttr = $item['sku_attr'] ?? null;

            $itemData = [
                'product_count' => $qty,
                'product_id' => $prodId,
            ];

            if (! empty($skuId)) {
                $itemData['sku_define_type'] = 'sku_id';
                $itemData['sku_id'] = $skuId;

                if (empty($skuAttr)) {
                    // Preflight dynamic sku_attr resolution
                    try {
                        $res = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
                            'product_id' => $prodId,
                            'ship_to_country' => $shippingAddress['country'] ?: 'SA',
                            'target_currency' => $draft->currencyCode ?: 'USD',
                            'target_language' => 'en',
                        ]);
                        if ($res['ok']) {
                            $variants = data_get($res['body'], 'aliexpress_ds_product_get_response.result.ae_item_sku_info_dtos.ae_item_sku_info_d_t_o', []);
                            foreach ($variants as $v) {
                                if (($v['sku_id'] ?? '') == $skuId && ! empty($v['sku_attr'])) {
                                    $skuAttr = $v['sku_attr'];
                                    break;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::channel('aliexpress')->warning('Live sku_attr resolution error: '.$e->getMessage());
                    }
                }

                if (! empty($skuAttr)) {
                    $itemData['sku_attr'] = $skuAttr;
                }
            }

            if (! empty($item['logistics_service_name'])) {
                $itemData['logistics_service_name'] = $item['logistics_service_name'];
            }

            $productItems[] = $itemData;
        }

        $params = [
            'param_place_order_request4_open_api_d_t_o' => [
                'out_order_id' => (string) $draft->correlationKey,
                'logistics_address' => $shippingAddress,
                'product_items' => $productItems,
            ],
        ];

        // 5. Invoke API call
        try {
            $response = $this->apiClient->call('aliexpress.ds.order.create', $token->access_token, $params);
        } catch (\Throwable $e) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'API_TRANSPORT_ERROR',
                errorMessageMasked: $e->getMessage(),
                providerRequestId: null,
                retryClassification: 'transient'
            );
        }

        $body = $response['body'] ?? [];
        $code = $response['code'] ?? ($body['error_response']['code'] ?? null);
        $message = $response['message'] ?? ($body['error_response']['msg'] ?? 'Order creation failed');
        $requestId = $body['error_response']['request_id'] ?? ($body['aliexpress_ds_order_create_response']['_trace_id_'] ?? null);

        // 6. Check for transport or API error envelope
        if (! $response['ok'] || ! empty($body['error_response']) || ($code !== null && (string) $code !== '0')) {
            return new ExternalOrderSubmissionFailed(
                errorCode: (string) ($code ?? 'API_ERROR'),
                errorMessageMasked: (string) $message,
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        // 7. Check for business failure inside response result
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $result = $resp['result'] ?? [];

        if (isset($result['is_success']) && ! $result['is_success']) {
            $errCode = $result['error_code'] ?? 'BUSINESS_ERROR';
            $errMsg = $result['error_msg'] ?? 'AliExpress business validation failed.';

            return new ExternalOrderSubmissionFailed(
                errorCode: (string) $errCode,
                errorMessageMasked: (string) $errMsg,
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        // 8. Extract official numeric external order ID
        $extractedId = $this->parseAuthoritativeOrderId($body);

        if (empty($extractedId) || ! ctype_digit($extractedId)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'EMPTY_EXTERNAL_ORDER_ID',
                errorMessageMasked: 'AliExpress returned HTTP 200 but without authoritative numeric order ID.',
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        return new VerifiedExternalOrderCreated(
            externalOrderId: $extractedId,
            providerRequestId: $requestId,
            providerStatus: 'WAIT_BUYER_PAY',
            responseMetadata: $this->redactSensitivePayload($body)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(string $officialExternalOrderId, ?int $providerAccountId = null): AliExpressOrderSnapshot
    {
        $token = $this->resolveToken($providerAccountId);
        if ($token === null || empty($token->access_token)) {
            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: 'OAUTH_TOKEN_MISSING',
                rawStatus: 'OAUTH_TOKEN_MISSING'
            );
        }

        try {
            $result = $this->apiClient->call('aliexpress.ds.order.get', $token->access_token, [
                'order_id' => $officialExternalOrderId,
            ]);

            if (! $result['ok']) {
                $code = $result['code'] ?? 'API_ERROR';

                return new AliExpressOrderSnapshot(
                    externalOrderId: $officialExternalOrderId,
                    orderStatus: 'QUERY_FAILED',
                    rawStatus: (string) $code,
                    rawResponse: $result['body'] ?? []
                );
            }

            $body = $result['body'] ?? [];
            $resp = $body['aliexpress_ds_order_get_response'] ?? $body;
            $res = $resp['result'] ?? [];

            $rawState = $res['order_status'] ?? 'UNKNOWN';
            $trackingNumber = $res['logistics_info_list']['logistics_info'][0]['logistics_no'] ?? null;
            $trackingCompany = $res['logistics_info_list']['logistics_info'][0]['logistics_service_name'] ?? null;

            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: (string) $rawState,
                trackingNumber: $trackingNumber ? (string) $trackingNumber : null,
                carrierName: $trackingCompany ? (string) $trackingCompany : null,
                rawStatus: (string) $rawState,
                rawResponse: $this->redactSensitivePayload($body)
            );
        } catch (\Throwable $e) {
            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: 'TRANSPORT_ERROR',
                rawStatus: $e->getMessage()
            );
        }
    }

    /**
     * Resolve OAuth Token.
     */
    protected function resolveToken(?int $accountId = null): ?AliExpressToken
    {
        if ($accountId) {
            return $this->oauthService->getTokenById($accountId);
        }

        return $this->oauthService->latestToken();
    }

    /**
     * Normalize and sanitize address fields.
     */
    protected function normalizeAddress(array $addr): array
    {
        return [
            'contact_person' => (string) ($addr['contact_person'] ?? 'Higesto Warehouse'),
            'phone_num' => (string) ($addr['phone_num'] ?? $addr['mobile_no'] ?? ''),
            'mobile_no' => (string) ($addr['mobile_no'] ?? $addr['phone_num'] ?? ''),
            'phone_country' => (string) ($addr['phone_country'] ?? '966'),
            'address' => (string) ($addr['address'] ?? ''),
            'city' => (string) ($addr['city'] ?? 'Riyadh'),
            'province' => (string) ($addr['province'] ?? $addr['state'] ?? 'Riyadh'),
            'zip' => (string) ($addr['zip'] ?? $addr['postcode'] ?? '11564'),
            'country' => strtoupper((string) ($addr['country'] ?? 'SA')),
            'company_name' => (string) ($addr['company_name'] ?? 'Higesto Warehouse'),
        ];
    }

    /**
     * Resolve international phone country code without plus sign.
     */
    protected function getPhoneCountry(string $country): string
    {
        $map = [
            'SA' => '966',
            'YE' => '967',
            'AE' => '971',
            'US' => '1',
            'GB' => '44',
            'CA' => '1',
        ];

        return $map[strtoupper($country)] ?? '966';
    }

    /**
     * Extract authoritative numeric order ID from response body.
     */
    protected function parseAuthoritativeOrderId(array $body): ?string
    {
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $res = $resp['result'] ?? [];

        if (isset($res['order_list']['number'])) {
            $num = $res['order_list']['number'];
            if (is_array($num) && ! empty($num[0])) {
                return (string) $num[0];
            }
            if (is_scalar($num)) {
                return (string) $num;
            }
        }

        if (isset($res['order_id']) && is_scalar($res['order_id'])) {
            return (string) $res['order_id'];
        }

        if (isset($res['order_list']) && is_array($res['order_list'])) {
            foreach ($res['order_list'] as $item) {
                if (is_scalar($item) && ctype_digit((string) $item)) {
                    return (string) $item;
                }
                if (isset($item['order_id']) && ctype_digit((string) $item['order_id'])) {
                    return (string) $item['order_id'];
                }
            }
        }

        return null;
    }

    /**
     * Pick the best shipping option from freight query response.
     */
    protected function pickBestShippingOption(array $options, string $currency): array
    {
        $best = null;
        $bestCost = null;

        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $cost = isset($opt['shipping_fee_cent']) && is_numeric($opt['shipping_fee_cent'])
                ? (float) $opt['shipping_fee_cent']
                : (float) ($opt['shipping_fee_amount'] ?? 0.0);

            if ($best === null || $cost < $bestCost) {
                $best = $opt;
                $bestCost = $cost;
            }
        }

        $best ??= $options[0];

        return [
            'service_name' => $best['service_name'] ?? $best['code'] ?? null,
            'code' => $best['code'] ?? null,
            'cost' => $bestCost ?? 0.0,
            'currency' => (string) ($best['shipping_fee_currency'] ?? $currency),
            'min_days' => $best['min_delivery_days'] ?? null,
            'max_days' => $best['max_delivery_days'] ?? ($best['guaranteed_delivery_days'] ?? null),
            'tracking' => (bool) ($best['tracking'] ?? false),
        ];
    }

    /**
     * Redact sensitive personal data and secrets from payload logs.
     */
    protected function redactSensitivePayload(array $payload): array
    {
        $redacted = $payload;
        array_walk_recursive($redacted, function (&$value, $key) {
            if (in_array(strtolower((string) $key), ['access_token', 'refresh_token', 'app_secret', 'phone_num', 'mobile_no', 'address', 'contact_person'], true)) {
                $value = '[REDACTED]';
            }
        });

        return $redacted;
    }
}
