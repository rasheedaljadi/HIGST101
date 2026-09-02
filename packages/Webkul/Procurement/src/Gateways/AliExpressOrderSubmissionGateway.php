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

        $meta = [];
        if (! empty($warehouse->description) && str_starts_with(trim($warehouse->description), '{')) {
            $meta = json_decode($warehouse->description, true) ?? [];
        }

        $candidate = [
            'contact_person' => trim((string) ($warehouse->contact_name ?? $warehouse->name ?? '')),
            'company_name' => trim((string) ($meta['company_name'] ?? $warehouse->name ?? $warehouse->contact_name ?? '')),
            'phone_num' => trim((string) ($warehouse->contact_number ?? '')),
            'mobile_no' => trim((string) ($warehouse->contact_number ?? '')),
            'phone_country' => trim((string) ($meta['phone_country'] ?? '966')),
            'address' => trim((string) ($warehouse->street ?? $warehouse->address1 ?? '')),
            'address2' => trim((string) ($meta['address2'] ?? '')),
            'district' => trim((string) ($meta['district'] ?? '')),
            'city' => trim((string) ($warehouse->city ?? '')),
            'province' => trim((string) ($warehouse->state ?? '')),
            'zip' => trim((string) ($warehouse->postcode ?? '')),
            'country' => strtoupper(trim((string) ($warehouse->country ?? 'SA'))),
            'short_address' => trim((string) ($meta['short_address'] ?? '')),
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

                if (! empty($item['sku_attr'])) {
                    $skuAttrMap[$sId] = (string) $item['sku_attr'];
                    $skuIdMap[$sId] = $sId;

                    continue;
                }

                if (! isset($productCache[$pId])) {
                    // 1. Try local database import snapshot first
                    $localImport = DB::table('aliexpress_product_imports')
                        ->where('aliexpress_product_id', $pId)
                        ->first();

                    if ($localImport && ! empty($localImport->payload_snapshot)) {
                        $snap = json_decode((string) $localImport->payload_snapshot, true);
                        $variants = $snap['variants'] ?? [];
                        if (! empty($variants)) {
                            $hasValidSkuAttr = false;
                            foreach ($variants as $v) {
                                if (! empty($v['sku_attr'])) {
                                    $hasValidSkuAttr = true;
                                    break;
                                }
                            }
                            if ($hasValidSkuAttr) {
                                $productCache[$pId] = $variants;
                            }
                        }
                    }

                    // 2. If not in local cache or lacks sku_attr, call product.get with safe rate-limit fallback
                    if (! isset($productCache[$pId])) {
                        $prodRes = $this->apiClient->call('aliexpress.ds.product.get', $auth->accessToken, [
                            'product_id' => $pId,
                            'ship_to_country' => $country,
                            'target_currency' => $draft->currencyCode ?: 'USD',
                            'target_language' => 'en',
                        ]);

                        if ($prodRes['ok'] && empty($prodRes['body']['error_response'])) {
                            $body = $prodRes['body'];
                            $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
                            $result = $resp['result'] ?? [];
                            $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
                            if (isset($variants['sku_id'])) {
                                $variants = [$variants];
                            }
                            $productCache[$pId] = $variants;
                        } else {
                            // Rate limit or transient error fallback: use sku_id directly
                            $productCache[$pId] = [['sku_id' => $sId, 'sku_attr' => '']];
                        }
                    }
                }

                $resolvedItemSkuAttr = '';
                $resolvedItemSkuId = $sId;
                $resolvedItemStock = null;
                if (! empty($productCache[$pId])) {
                    foreach ($productCache[$pId] as $v) {
                        if (($v['sku_id'] ?? '') == $sId) {
                            $resolvedItemSkuAttr = (string) ($v['sku_attr'] ?? '');
                            $resolvedItemSkuId = (string) ($v['sku_id'] ?? $sId);
                            if (isset($v['sku_available_stock'])) {
                                $resolvedItemStock = (int) $v['sku_available_stock'];
                            } elseif (isset($v['ipm_sku_stock'])) {
                                $resolvedItemStock = (int) $v['ipm_sku_stock'];
                            } elseif (isset($v['stock'])) {
                                $resolvedItemStock = (int) $v['stock'];
                            }
                            break;
                        }
                    }
                }

                $skuAttrMap[$sId] = $resolvedItemSkuAttr;
                $skuIdMap[$sId] = $resolvedItemSkuId;
                $skuStockMap[$sId] = $resolvedItemStock;
            }
        } catch (\Throwable $e) {
            $skuAttrMap[$skuId] = '';
            $skuIdMap[$skuId] = $skuId;
            $skuStockMap[$skuId] = null;
        }

        $resolvedSkuAttr = $skuAttrMap[$skuId] ?? '';
        $resolvedFreightSkuId = $skuIdMap[$skuId] ?? $skuId;

        // Check if selected SKU is strictly out of stock on AliExpress
        if (isset($skuStockMap[$skuId]) && $skuStockMap[$skuId] !== null && $skuStockMap[$skuId] < $qty) {
            return new AliExpressOrderPreflight(
                isSuccess: false,
                isDeliverableToDestination: false,
                destinationCountry: $country,
                resolvedSkuAttr: $resolvedSkuAttr,
                errorCode: 'INVENTORY_HOLD_ERROR',
                errorMessage: self::mapAliExpressErrorMessage('INVENTORY_HOLD_ERROR', "المخزون غير متوفر لهذا الصنف لدى المورد في علي إكسبرس (نفد المخزون - الكمية المتاحة: {$skuStockMap[$skuId]})"),
                rawDetails: [
                    'available_stock' => $skuStockMap[$skuId],
                    'required_qty' => $qty,
                    'sku_attrs' => $skuAttrMap,
                    'resolved_sku_ids' => $skuIdMap,
                ]
            );
        }

        // 2. Query live freight options specifically for selected SKU (or use pre-selected service)
        $directService = $items[0]['logistics_service_name'] ?? null;
        if (! empty($directService)) {
            return new AliExpressOrderPreflight(
                isSuccess: true,
                isDeliverableToDestination: true,
                destinationCountry: $country,
                shippingServiceName: (string) $directService,
                shippingCost: 0.0,
                shippingCurrency: $draft->currencyCode ?: 'USD',
                minDeliveryDays: 5,
                maxDeliveryDays: 10,
                trackingAvailable: true,
                resolvedSkuAttr: $resolvedSkuAttr,
                shippingCostMinor: 0,
                shippingCostFormatted: 'US $0.00',
                moneyEvidence: ['source' => 'pre_selected_in_draft'],
                rawDetails: [
                    'sku_attrs' => $skuAttrMap,
                    'resolved_sku_ids' => $skuIdMap,
                ]
            );
        }

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
        if (! $preflight->isSuccess || ! $preflight->isDeliverableToDestination || empty($preflight->shippingServiceName)) {
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

            $itemPayload = [
                'product_count' => $qty > 0 ? (int) $qty : 1,
                'product_id' => is_numeric($prodId) ? (int) $prodId : $prodId,
                'logistics_service_name' => $preflight->shippingServiceName,
            ];
            if (! empty($skuAttr)) {
                $itemPayload['sku_attr'] = (string) $skuAttr;
            }
            $productItems[] = $itemPayload;
        }

        // Strict Unpaid creation payload (NO payment parameters, try_to_pay omitted or false)
        $params = [
            'param_place_order_request4_open_api_d_t_o' => [
                'out_order_id' => (string) $draft->correlationKey,
                'logistics_address' => $shippingAddress,
                'product_items' => $productItems,
            ],
        ];

        if (! empty($shippingAddress['country']) && strtoupper((string) $shippingAddress['country']) === 'SA') {
            $natCode = $shippingAddress['nat_addr'] ?? $shippingAddress['passport_no'] ?? $shippingAddress['short_address'] ?? 'RMAD3455';
            $params['ds_extend_request'] = [
                'trade_extra_param' => [
                    'business_model' => 'retail',
                    'nat_addr' => (string) $natCode,
                ],
                'payment' => [
                    'pay_currency' => 'USD',
                    'try_to_pay' => 'false',
                ],
            ];
        }

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
            $rawMsg = $message ?: ($body['error_response']['msg'] ?? null);
            $errCode = (string) ($code ?? $body['error_response']['sub_code'] ?? $body['error_response']['code'] ?? 'API_ERROR');
            $localizedMsg = self::mapAliExpressErrorMessage($errCode, $rawMsg);

            return new ExternalOrderSubmissionFailed(
                errorCode: $errCode,
                errorMessageMasked: $localizedMsg,
                providerRequestId: $requestId,
                retryClassification: 'fatal',
                rawResponse: $this->redactSensitivePayload($body)
            );
        }

        // 8. Strict Business Success Check (is_success MUST explicitly be true)
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $result = $resp['result'] ?? [];

        if (! isset($result['is_success']) || $result['is_success'] !== true) {
            $errCode = (string) ($result['error_code'] ?? 'ORDER_SUBMISSION_REJECTED');
            $rawMsg = $result['error_msg'] ?? null;
            $localizedMsg = self::mapAliExpressErrorMessage($errCode, $rawMsg);

            return new ExternalOrderSubmissionFailed(
                errorCode: $errCode,
                errorMessageMasked: $localizedMsg,
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
            $logisticsList = $res['logistics_info_list']['aeop_order_logistics_info']
                ?? $res['logistics_info_list']['logistics_info']
                ?? [];
            if (isset($logisticsList['logistics_no']) || isset($logisticsList['logistics_service']) || isset($logisticsList['tracking_no'])) {
                $logisticsList = [$logisticsList];
            }
            $firstLogistics = $logisticsList[0] ?? [];
            $trackingNumber = $firstLogistics['logistics_no'] ?? $firstLogistics['tracking_no'] ?? $firstLogistics['mail_no'] ?? null;
            $trackingCompany = $firstLogistics['logistics_service'] ?? $firstLogistics['logistics_service_name'] ?? $firstLogistics['company_name'] ?? null;

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

    /**
     * Map AliExpress machine error codes and raw English messages to clear, localized Arabic messages.
     */
    public static function mapAliExpressErrorMessage(string $errorCode, ?string $rawMessage = null): string
    {
        $code = strtoupper(trim($errorCode));

        $messages = [
            'INVENTORY_HOLD_ERROR' => 'المخزون غير متوفر لهذا الصنف لدى المورد في علي إكسبرس (نفد المخزون - Out of Stock)',
            'OUT_OF_STOCK' => 'المخزون غير متوفر لهذا الصنف لدى المورد في علي إكسبرس (نفد المخزون - Out of Stock)',
            'SKU_NOT_EXIST' => 'المتغير أو الصنف المطلوب غير متاح حالياً لدى المورد في علي إكسبرس (الـ SKU غير موجود أو تم تعديل خصائصه)',
            'PRODUCT_NOT_EXIST' => 'المنتج غير متوفر أو تم حذفه من متجر المورد في علي إكسبرس',
            'ITEM_NOT_EXIST' => 'المنتج غير متوفر أو تم حذفه من متجر المورد في علي إكسبرس',
            'ITEM_OFFLINE' => 'المنتج متوقف عن العرض والبيع حالياً لدى المورد على علي إكسبرس',
            'ITEM_DELETED' => 'تم حذف هذا المنتج من متجر المورد على علي إكسبرس',
            'PRODUCT_CANNOT_DELIVER_TO_COUNTRY' => 'المورد لا يدعم الشحن إلى الوجهة المحددة (المملكة العربية السعودية)',
            'DELIVER_NOT_SUPPORT' => 'المورد لا يدعم الشحن إلى الوجهة المحددة (المملكة العربية السعودية)',
            'NOT_SUPPORT_DELIVERY' => 'المورد لا يدعم الشحن إلى الوجهة المحددة (المملكة العربية السعودية)',
            'SHIPPING_SERVICE_NOT_AVAILABLE' => 'طريقة الشحن المطلوبة غير متوفرة لهذا المنتج إلى الوجهة المحددة',
            'NO_SKU_SPECIFIC_SHIPPING_OPTION' => 'لا تتوفر خيارات شحن لهذا المتغير إلى الوجهة المحددة',
            'BUYER_NOT_LEGAL' => 'حساب المشتري على علي إكسبرس مقيد أو غير مصرح له بإنشاء طلبات دروب شيبنج',
            'ACCOUNT_UNAUTHORIZED' => 'انتهت صلاحية جلسة الربط مع علي إكسبرس، يرجى إعادة تسجيل الدخول من إدارة المفاتيح',
            'TOKEN_EXPIRED' => 'انتهت صلاحية رمز الوصول (Access Token) في علي إكسبرس، يرجى إعادة توثيق الحساب',
            'ORDER_CREATION_LIMIT_EXCEEDED' => 'تم تجاوز الحد الأقصى المسموح به لإنشاء الطلبات مؤقتاً على علي إكسبرس',
            'FREQUENCY_LIMIT' => 'تم تجاوز حد الاستعلامات المسموح به على علي إكسبرس، يرجى المحاولة بعد لحظات',
            'SHIPPING_ADDRESS_INVALID' => 'بيانات عنوان الشحن غير صالحة وفق متطلبات الشحن لدى علي إكسبرس',
            'LOGISTICS_ADDRESS_INVALID' => 'بيانات عنوان الشحن غير صالحة وفق متطلبات الشحن لدى علي إكسبرس',
            'PRICE_CHANGED' => 'تغير سعر المنتج لدى المورد على علي إكسبرس، يرجى مراجعة وتحديث التكلفة',
            'PAYMENT_UNSUPPORTED' => 'طريقة الدفع أو العملة غير مدعومة لهذا الطلب لدى علي إكسبرس',
            'EMPTY_OR_NON_NUMERIC_EXTERNAL_ORDER_ID' => 'لم يقم علي إكسبرس بإرجاع رقم طلب رسمي معتمد',
            'PREFLIGHT_VALIDATION_FAILED' => 'فشل التحقق المسبق من توفر المنتج أو خيارات الشحن لدى المورد',
            'EMPTY_ITEMS_DRAFT' => 'أمر الشراء لا يحتوي على أي منتجات للإرسال',
            'SHIPPING_ADDRESS_NOT_CONFIGURED' => 'عنوان المستودع الرئيسي غير مهيأ في النظام لإرسال الشحنات',
            'API_TRANSPORT_ERROR' => 'تعذر الاتصال بخوادم علي إكسبرس (خطأ في شبكة الاتصال)',
        ];

        if (isset($messages[$code])) {
            return $messages[$code];
        }

        foreach ($messages as $key => $msg) {
            if (str_contains($code, $key)) {
                return $msg;
            }
        }

        if (! empty($rawMessage) && $rawMessage !== 'Order creation failed' && $rawMessage !== 'AliExpress order creation rejected or is_success was false.') {
            return "فشل إنشاء الطلب لدى علي إكسبرس [{$errorCode}]: {$rawMessage}";
        }

        return "فشل إنشاء الطلب لدى علي إكسبرس بسبب استجابة المورد [كود الخطأ: {$errorCode}]";
    }
}
