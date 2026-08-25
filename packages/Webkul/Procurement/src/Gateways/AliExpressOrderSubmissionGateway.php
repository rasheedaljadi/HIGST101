<?php

namespace Webkul\Procurement\Gateways;

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use App\Services\AliExpress\Exceptions\AliExpressInvalidShippingAddressException;
use App\Services\AliExpress\Shipping\AliExpressShippingAddressValidator;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Webkul\Procurement\Contracts\AliExpressAuthorizationContextResolver;
use Webkul\Procurement\Contracts\AliExpressOrderGateway;
use Webkul\Procurement\DTO\AliExpressOrderPreflight;
use Webkul\Procurement\DTO\AliExpressOrderSnapshot;
use Webkul\Procurement\DTO\ExternalOrderDraft;
use Webkul\Procurement\DTO\ExternalOrderSubmissionFailed;
use Webkul\Procurement\DTO\VerifiedExternalOrderCreated;
use Webkul\Procurement\Exceptions\AliExpressAuthorizationUnavailableException;
use Webkul\Procurement\Support\AliExpressMoneyNormalizer;

class AliExpressOrderSubmissionGateway implements AliExpressOrderGateway
{
    public function __construct(
        protected AliExpressApiClient $apiClient,
        protected AliExpressOAuthService $oauthService,
        protected ?AliExpressAuthorizationContextResolver $authResolver = null
    ) {
        $this->authResolver ??= app(AliExpressAuthorizationContextResolver::class);
    }

    /**
     * Resolve default Saudi warehouse shipping address configured in Key Management.
     *
     * @return array{contact_person: string, phone_num: string, mobile_no: string, phone_country: string, address: string, city: string, province: string, zip: string, country: string, company_name: string}
     *
     * @throws DomainException
     */
    public function resolveWarehouseShippingAddress(?array $override = null): array
    {
        if (! empty($override)) {
            if (! app()->environment('testing')) {
                throw new DomainException('SHIPPING_ADDRESS_OVERRIDE_FORBIDDEN: Address override is strictly forbidden outside testing.');
            }

            return $this->normalizeAddress($override);
        }

        $warehouse = DB::table('inventory_sources')
            ->where('code', 'default')
            ->first();

        if (! $warehouse) {
            throw new DomainException('SHIPPING_ADDRESS_NOT_CONFIGURED: Key Management inventory source [default] is not configured in database.');
        }

        $candidate = [
            'contact_person' => trim((string) ($warehouse->contact_name ?? $warehouse->name ?? '')),
            'phone_num' => trim((string) ($warehouse->contact_number ?? '')),
            'mobile_no' => trim((string) ($warehouse->contact_number ?? '')),
            'phone_country' => '966',
            'address' => trim((string) ($warehouse->street ?? $warehouse->address1 ?? '')),
            'city' => trim((string) ($warehouse->city ?? '')),
            'province' => trim((string) ($warehouse->state ?? '')),
            'zip' => trim((string) ($warehouse->postcode ?? '')),
            'country' => strtoupper(trim((string) ($warehouse->country ?? 'SA'))),
            'company_name' => trim((string) ($warehouse->name ?? $warehouse->contact_name ?? '')),
        ];

        return AliExpressShippingAddressValidator::normalizeAndValidate($candidate)->toLogisticsAddressArray();
    }

    /**
     * {@inheritdoc}
     */
    public function preflight(ExternalOrderDraft $draft): AliExpressOrderPreflight
    {
        try {
            $auth = $this->authResolver->resolveForDropshipperSubmission(
                $draft->providerAccountId ? (string) $draft->providerAccountId : null
            );
        } catch (AliExpressAuthorizationUnavailableException $e) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: 'SA',
                errorCode: $e->errorCode,
                errorMessage: $e->getMessage(),
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

        try {
            $shippingAddress = $this->resolveWarehouseShippingAddress($draft->overrideShippingAddress);
        } catch (DomainException $e) {
            $errorCode = ($e instanceof AliExpressInvalidShippingAddressException) ? $e->errorCode : 'SHIPPING_ADDRESS_NOT_CONFIGURED';

            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: 'SA',
                errorCode: $errorCode,
                errorMessage: $e->getMessage(),
                rawDetails: []
            );
        }

        $country = $shippingAddress['country'] ?: 'SA';

        // 1. Resolve product details & exact sku_attr for all items in draft
        $skuAttrMap = [];
        $skuIdMap = [];
        $productCache = [];

        try {
            foreach ($items as $item) {
                $pId = (string) ($item['supplier_product_id'] ?? '');
                $sId = (string) ($item['supplier_sku_id'] ?? '');

                if (! isset($productCache[$pId])) {
                    $prodRes = $this->apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
                        'product_id' => $pId,
                        'ship_to_country' => $country,
                        'target_currency' => $draft->currencyCode ?: 'USD',
                        'target_language' => 'en',
                    ]);

                    if (! $prodRes['ok'] || ! empty($prodRes['body']['error_response'])) {
                        $msg = $prodRes['message'] ?? $prodRes['body']['error_response']['msg'] ?? 'Product lookup failed';

                        return new AliExpressOrderPreflight(
                            isSuccess: false,
                            isDeliverableToDestination: false,
                            destinationCountry: $country,
                            errorCode: 'SKU_ATTR_RESOLUTION_FAILED',
                            errorMessage: "AliExpress product inquiry failed: {$msg}",
                            rawDetails: $prodRes['body'] ?? []
                        );
                    }

                    $body = $prodRes['body'];
                    $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
                    $result = $resp['result'] ?? [];
                    $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
                    if (isset($variants['sku_id'])) {
                        $variants = [$variants];
                    }
                    $productCache[$pId] = $variants;
                }

                $resolvedItemSkuAttr = null;
                $resolvedItemSkuId = $sId;
                foreach ($productCache[$pId] as $v) {
                    if (($v['sku_id'] ?? '') == $sId && ! empty($v['sku_attr'])) {
                        $resolvedItemSkuAttr = (string) $v['sku_attr'];
                        $resolvedItemSkuId = (string) $v['sku_id'];
                        break;
                    }
                }

                // Fallback ONLY for genuinely simple products (single SKU)
                if (empty($resolvedItemSkuAttr) && ! empty($productCache[$pId])) {
                    if (count($productCache[$pId]) === 1) {
                        // Simple product: only one variant exists, safe to use
                        $firstVariant = $productCache[$pId][0];
                        $resolvedItemSkuAttr = (string) ($firstVariant['sku_attr'] ?? '');
                        $resolvedItemSkuId = (string) ($firstVariant['sku_id'] ?? $sId);
                    } else {
                        // Configurable product: MUST NOT fall back to random variant
                        return new AliExpressOrderPreflight(
                            isSuccess: false,
                            isDeliverableToDestination: false,
                            destinationCountry: $country,
                            errorCode: 'SKU_VARIANT_MISMATCH',
                            errorMessage: "SKU ID [{$sId}] does not match any of the ".count($productCache[$pId])." variants for product {$pId}. Cannot proceed with unverified variant.",
                            rawDetails: ['available_sku_ids' => array_column($productCache[$pId], 'sku_id')]
                        );
                    }
                }

                if (empty($resolvedItemSkuAttr)) {
                    return new AliExpressOrderPreflight(
                        isSuccess: false,
                        isDeliverableToDestination: false,
                        destinationCountry: $country,
                        errorCode: 'SKU_ATTR_RESOLUTION_FAILED',
                        errorMessage: "Exact sku_attr could not be resolved for product ID {$pId} and SKU ID {$sId}.",
                        rawDetails: []
                    );
                }

                $skuAttrMap[$sId] = $resolvedItemSkuAttr;
                $skuIdMap[$sId] = $resolvedItemSkuId;
            }
        } catch (\Throwable $e) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: $country,
                errorCode: 'SKU_ATTR_RESOLUTION_FAILED',
                errorMessage: 'Exception during product sku_attr resolution: '.$e->getMessage(),
                rawDetails: []
            );
        }

        $resolvedSkuAttr = $skuAttrMap[$skuId] ?? null;
        $resolvedFreightSkuId = $skuIdMap[$skuId] ?? null;

        if (empty($resolvedSkuAttr) || empty($resolvedFreightSkuId)) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: $country,
                errorCode: 'PRIMARY_SKU_NOT_RESOLVED',
                errorMessage: 'Primary SKU ['.$skuId.'] could not be resolved. Available: '.implode(', ', array_keys($skuAttrMap)),
                rawDetails: ['available_sku_attrs' => $skuAttrMap, 'available_sku_ids' => $skuIdMap]
            );
        }

        // 2. Query live freight options specifically for selected SKU
        try {
            $freightReq = [
                'productId' => $productId,
                'shipToCountry' => $country,
                'quantity' => $qty > 0 ? $qty : 1,
                'currency' => $draft->currencyCode ?: 'USD',
                'language' => 'en_US',
                'locale' => 'en_US',
                'selectedSkuId' => $resolvedFreightSkuId,
            ];

            $freightRes = $this->apiClient->call('aliexpress.ds.freight.query', $auth->accessToken, [
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

            if (! is_array($options) || empty($options)) {
                return new AliExpressOrderPreflight(
                    isSuccess: false,
                    isDeliverableToDestination: false,
                    destinationCountry: $country,
                    resolvedSkuAttr: $resolvedSkuAttr,
                    errorCode: 'NO_SKU_SPECIFIC_SHIPPING_OPTION',
                    errorMessage: "No live shipping options available specifically for SKU ID {$skuId} to {$country}.",
                    rawDetails: $body
                );
            }

            if (isset($options['code']) || isset($options['shipping_fee_cent']) || isset($options['service_name'])) {
                $options = [$options];
            }

            $bestOption = $this->pickBestShippingOption($options, $draft->currencyCode ?: 'USD');

            if (! $bestOption['is_valid']) {
                return new AliExpressOrderPreflight(
                    isSuccess: false,
                    isDeliverableToDestination: false,
                    destinationCountry: $country,
                    resolvedSkuAttr: $resolvedSkuAttr,
                    errorCode: $bestOption['error_code'] ?? 'PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS',
                    errorMessage: $bestOption['error_message'] ?? 'Could not determine valid normalized shipping fee.',
                    rawDetails: $body
                );
            }

            return new AliExpressOrderPreflight(
                isSuccess: true,
                isDeliverableToDestination: true,
                destinationCountry: $country,
                shippingServiceName: $bestOption['service_name'],
                shippingCost: $bestOption['cost_decimal'],
                shippingCurrency: $bestOption['currency'],
                minDeliveryDays: $bestOption['min_days'],
                maxDeliveryDays: $bestOption['max_days'],
                trackingAvailable: $bestOption['tracking'],
                resolvedSkuAttr: $resolvedSkuAttr,
                shippingCostMinor: $bestOption['cost_minor'],
                shippingCostFormatted: $bestOption['cost_formatted'],
                moneyEvidence: $bestOption['money_evidence'],
                rawDetails: [
                    'available_options_count' => count($options),
                    'selected_option' => $bestOption,
                    'sku_attrs' => $skuAttrMap,
                    'resolved_sku_ids' => $skuIdMap,
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
        try {
            $auth = $this->authResolver->resolveForDropshipperSubmission(
                $draft->providerAccountId ? (string) $draft->providerAccountId : null
            );
        } catch (AliExpressAuthorizationUnavailableException $e) {
            return new ExternalOrderSubmissionFailed(
                errorCode: $e->errorCode,
                errorMessageMasked: $e->getMessage(),
                providerRequestId: null,
                retryClassification: 'non_retryable'
            );
        }

        // 3. Resolve shipping address strictly
        try {
            $shippingAddress = $this->resolveWarehouseShippingAddress($draft->overrideShippingAddress);
        } catch (DomainException $e) {
            $errorCode = ($e instanceof AliExpressInvalidShippingAddressException) ? $e->errorCode : 'SHIPPING_ADDRESS_NOT_CONFIGURED';

            return new ExternalOrderSubmissionFailed(
                errorCode: $errorCode,
                errorMessageMasked: $e->getMessage(),
                providerRequestId: null,
                retryClassification: 'fatal'
            );
        }

        // 4. Mandatory Preflight Validation of Draft before creation
        $preflight = $this->preflight($draft);
        if (! $preflight->isSuccess || ! $preflight->isDeliverableToDestination || empty($preflight->resolvedSkuAttr) || empty($preflight->shippingServiceName)) {
            return new ExternalOrderSubmissionFailed(
                errorCode: $preflight->errorCode ?: 'PREFLIGHT_VALIDATION_FAILED',
                errorMessageMasked: $preflight->errorMessage ?: 'Draft preflight validation failed before submission.',
                providerRequestId: null,
                retryClassification: 'non_retryable',
                rawResponse: $preflight->rawDetails
            );
        }

        // 5. Build product items using ONLY verified Preflight outputs
        $skuAttrMap = $preflight->rawDetails['sku_attrs'] ?? [];
        $skuIdMap = $preflight->rawDetails['resolved_sku_ids'] ?? [];
        $productItems = [];
        foreach ($draft->items as $item) {
            $prodId = (string) ($item['supplier_product_id'] ?? '');
            $origSkuId = (string) ($item['supplier_sku_id'] ?? '');
            $skuId = $skuIdMap[$origSkuId] ?? $origSkuId;
            $qty = (int) ($item['qty'] ?? 1);
            $skuAttr = $skuAttrMap[$origSkuId] ?? $skuAttrMap[$skuId] ?? $preflight->resolvedSkuAttr;

            $productItems[] = [
                'product_count' => $qty > 0 ? $qty : 1,
                'product_id' => $prodId,
                'sku_id' => $skuId,
                'sku_attr' => $skuAttr,
                'sku_define_type' => 'sku_id',
                'logistics_service_name' => $preflight->shippingServiceName,
            ];
        }

        // Strict Unpaid creation payload (NO payment parameters, try_to_pay omitted or false)
        $params = [
            'param_place_order_request4_open_api_d_t_o' => [
                'out_order_id' => (string) $draft->correlationKey,
                'logistics_address' => $shippingAddress,
                'product_items' => $productItems,
            ],
        ];

        // 6. Invoke API call
        try {
            $response = $this->apiClient->call('aliexpress.ds.order.create', $auth->accessToken, $params);
        } catch (\Throwable $e) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'API_TRANSPORT_ERROR',
                errorMessageMasked: 'AliExpress API connection failed.',
                providerRequestId: null,
                retryClassification: 'transient'
            );
        }

        $body = $response['body'] ?? [];
        $code = $response['code'] ?? ($body['error_response']['code'] ?? null);
        $message = $response['message'] ?? ($body['error_response']['msg'] ?? 'Order creation failed');
        $requestId = $body['error_response']['request_id'] ?? ($body['aliexpress_ds_order_create_response']['_trace_id_'] ?? null);

        // 7. Check for transport or API error envelope
        if (! $response['ok'] || ! empty($body['error_response']) || ($code !== null && (string) $code !== '0' && (string) $code !== '200')) {
            return new ExternalOrderSubmissionFailed(
                errorCode: (string) ($code ?? 'API_ERROR'),
                errorMessageMasked: (string) $message,
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        // 8. Strict Business Success Check (is_success MUST explicitly be true)
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $result = $resp['result'] ?? [];

        if (! isset($result['is_success']) || $result['is_success'] !== true) {
            $errCode = $result['error_code'] ?? 'ORDER_SUBMISSION_REJECTED';
            $errMsg = $result['error_msg'] ?? 'AliExpress order creation rejected or is_success was false.';

            return new ExternalOrderSubmissionFailed(
                errorCode: (string) $errCode,
                errorMessageMasked: (string) $errMsg,
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        // 9. Extract official numeric external order ID
        $extractedId = $this->parseAuthoritativeOrderId($body);

        if (empty($extractedId) || ! ctype_digit($extractedId) || strlen($extractedId) < 10) {
            return new ExternalOrderSubmissionFailed(
                errorCode: 'EMPTY_OR_NON_NUMERIC_EXTERNAL_ORDER_ID',
                errorMessageMasked: 'AliExpress returned success envelope without valid authoritative numeric order ID.',
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
        // 1. Upfront numeric validation - Reject invalid/non-numeric/synthetic IDs immediately
        if (empty($officialExternalOrderId) || ! ctype_digit($officialExternalOrderId) || strlen($officialExternalOrderId) < 10) {
            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: 'INVALID_EXTERNAL_ORDER_ID',
                rawStatus: 'NON_NUMERIC_OR_EMPTY_ORDER_ID'
            );
        }

        try {
            $auth = $this->authResolver->resolveForDropshipperSubmission(
                $providerAccountId ? (string) $providerAccountId : null
            );
        } catch (AliExpressAuthorizationUnavailableException $e) {
            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: 'AUTH_UNAVAILABLE',
                rawStatus: $e->getMessage()
            );
        }

        try {
            $result = $this->apiClient->call('aliexpress.trade.ds.order.get', $auth->accessToken, [
                'single_order_query' => json_encode(['order_id' => (string) $officialExternalOrderId]),
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
            $resp = $body['aliexpress_trade_ds_order_get_response'] ?? $body;
            $res = $resp['result'] ?? [];

            $rawState = $res['order_status'] ?? 'UNKNOWN';
            $trackingNumber = $res['logistics_info_list']['logistics_info'][0]['logistics_no'] ?? null;
            $trackingCompany = $res['logistics_info_list']['logistics_info'][0]['logistics_service_name'] ?? null;

            $overTimeLeft = null;
            if (isset($res['over_time_left']) && is_numeric($res['over_time_left'])) {
                $overTimeLeft = (int) $res['over_time_left'];
            } elseif (isset($res['left_time']) && is_numeric($res['left_time'])) {
                $overTimeLeft = (int) $res['left_time'];
            } elseif (isset($res['timeout_left']) && is_numeric($res['timeout_left'])) {
                $overTimeLeft = (int) $res['timeout_left'];
            }

            $payTimeoutSecond = null;
            if (isset($res['pay_timeout_second']) && is_numeric($res['pay_timeout_second'])) {
                $payTimeoutSecond = (int) $res['pay_timeout_second'];
            }

            $paymentDeadlineAt = null;
            if (! empty($res['expire_time'])) {
                $paymentDeadlineAt = Carbon::parse($res['expire_time'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))->toIso8601String();
            } elseif (! empty($res['gmt_pay_deadline'])) {
                $paymentDeadlineAt = Carbon::parse($res['gmt_pay_deadline'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))->toIso8601String();
            } elseif (! empty($res['end_time'])) {
                $paymentDeadlineAt = Carbon::parse($res['end_time'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))->toIso8601String();
            } elseif ($payTimeoutSecond !== null) {
                $baseTime = ! empty($res['gmt_create'])
                    ? Carbon::parse($res['gmt_create'], 'Asia/Shanghai')->setTimezone(config('app.timezone'))
                    : now();
                $deadlineCarbon = $baseTime->copy()->addSeconds($payTimeoutSecond);
                $paymentDeadlineAt = $deadlineCarbon->toIso8601String();
                if ($overTimeLeft === null) {
                    $overTimeLeft = max(0, (int) now()->diffInSeconds($deadlineCarbon, false));
                }
            } elseif ($overTimeLeft !== null && $overTimeLeft > 0) {
                $paymentDeadlineAt = now()->addSeconds($overTimeLeft)->toIso8601String();
            }

            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: (string) $rawState,
                trackingNumber: $trackingNumber ? (string) $trackingNumber : null,
                carrierName: $trackingCompany ? (string) $trackingCompany : null,
                rawStatus: (string) $rawState,
                rawResponse: $this->redactSensitivePayload($body),
                overTimeLeft: $overTimeLeft,
                paymentDeadlineAt: $paymentDeadlineAt
            );
        } catch (\Throwable $e) {
            return new AliExpressOrderSnapshot(
                externalOrderId: $officialExternalOrderId,
                orderStatus: 'TRANSPORT_ERROR',
                rawStatus: 'AliExpress query transport error.'
            );
        }
    }

    /**
     * Normalize and sanitize address fields for testing adapter.
     */
    protected function normalizeAddress(array $addr): array
    {
        return AliExpressShippingAddressValidator::normalizeAndValidate($addr)->toLogisticsAddressArray();
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
     * Pick and normalize the best shipping option from freight query response.
     *
     * @return array{
     *     is_valid: bool,
     *     service_name: ?string,
     *     cost_minor: int,
     *     cost_decimal: float,
     *     cost_formatted: string,
     *     currency: string,
     *     min_days: ?int,
     *     max_days: ?int,
     *     tracking: bool,
     *     money_evidence: array,
     *     error_code: ?string,
     *     error_message: ?string
     * }
     */
    protected function pickBestShippingOption(array $options, string $currency): array
    {
        $best = null;
        $bestNorm = null;

        foreach ($options as $opt) {
            if (! is_array($opt)) {
                continue;
            }

            $serviceName = (string) ($opt['service_name'] ?? $opt['code'] ?? $opt['company'] ?? '');
            if (empty($serviceName)) {
                continue;
            }

            $norm = AliExpressMoneyNormalizer::normalizeFreightOption($opt, $currency);
            if (! $norm['is_valid']) {
                continue;
            }

            if ($bestNorm === null || $norm['normalized_minor'] < $bestNorm['normalized_minor']) {
                $best = $opt;
                $bestNorm = $norm;
            }
        }

        if ($best === null || $bestNorm === null) {
            return [
                'is_valid' => false,
                'service_name' => null,
                'cost_minor' => 0,
                'cost_decimal' => 0.0,
                'cost_formatted' => '0.00',
                'currency' => $currency,
                'min_days' => null,
                'max_days' => null,
                'tracking' => false,
                'money_evidence' => [],
                'error_code' => 'NO_VALID_SHIPPING_SERVICE_FOUND',
                'error_message' => 'No shipping service option had a valid service name and normalized fee.',
            ];
        }

        return [
            'is_valid' => true,
            'service_name' => (string) ($best['service_name'] ?? $best['code'] ?? 'CAINIAO_STANDARD'),
            'cost_minor' => $bestNorm['normalized_minor'],
            'cost_decimal' => (float) ($bestNorm['normalized_minor'] / 100),
            'cost_formatted' => $bestNorm['formatted_decimal'],
            'currency' => $bestNorm['currency'],
            'min_days' => isset($best['min_delivery_days']) ? (int) $best['min_delivery_days'] : null,
            'max_days' => isset($best['max_delivery_days']) ? (int) $best['max_delivery_days'] : (isset($best['guaranteed_delivery_days']) ? (int) $best['guaranteed_delivery_days'] : null),
            'tracking' => (bool) ($best['tracking'] ?? false),
            'money_evidence' => $bestNorm,
            'error_code' => null,
            'error_message' => null,
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
