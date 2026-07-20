<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use App\Services\AliExpress\AliExpressApiClient;
use App\Services\AliExpress\AliExpressOAuthService;
use Webkul\Fulfillment\Contracts\FulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;
use Webkul\Fulfillment\Models\PurchaseOrder;

class AliExpressFulfillmentProvider implements FulfillmentProviderInterface
{
    /**
     * Create a new provider instance.
     */
    public function __construct(
        protected AliExpressOAuthService $oauthService,
        protected AliExpressApiClient $apiClient
    ) {}

    /**
     * Unique identifier for the provider.
     */
    public function code(): string
    {
        return 'aliexpress';
    }

    /**
     * Determine if the provider is configured and ready.
     */
    public function isConfigured(): bool
    {
        return $this->oauthService->isConfigured();
    }

    /**
     * Submit an order request to the supplier.
     */
    public function createSupplierOrder(SupplierOrderRequest $request): SupplierOrderResult
    {
        $token = $request->providerAccountId
            ? $this->oauthService->getTokenById($request->providerAccountId)
            : $this->oauthService->latestToken();

        if ($token === null) {
            return SupplierOrderResult::failure(
                isRetryable: false,
                code: 'OAUTH_TOKEN_MISSING',
                message: 'AliExpress OAuth token is missing or expired and could not be refreshed.'
            );
        }

        $items = array_map(function ($item) use ($token) {
            $data = [
                'product_count' => (int) $item->qty,
                'product_id'    => (string) $item->aliexpressProductId,
            ];

            if ($item->skuId !== null && $item->skuId !== '') {
                $data['sku_define_type'] = 'sku_id';
                $data['sku_id'] = (string) $item->skuId;

                // Fetch live product details to resolve the exact sku_attr from AliExpress
                try {
                    $res = $this->apiClient->call('aliexpress.ds.product.get', $token->access_token, [
                        'product_id'      => $item->aliexpressProductId,
                        'ship_to_country' => config('aliexpress.import.ship_to_country', 'SA'),
                        'target_currency' => config('aliexpress.import.target_currency', 'USD'),
                        'target_language' => config('aliexpress.import.primary_language', 'en'),
                    ]);

                    if ($res['ok']) {
                        $body = $res['body'];
                        $resp = $body['aliexpress_ds_product_get_response'] ?? $body;
                        $result = $resp['result'] ?? [];
                        $variants = $result['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
                        foreach ($variants as $v) {
                            if (($v['sku_id'] ?? '') == $item->skuId && !empty($v['sku_attr'])) {
                                $data['sku_attr'] = $v['sku_attr'];
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::channel('aliexpress')->warning('Live sku_attr resolution failed: ' . $e->getMessage());
                }
            }

            return $data;
        }, $request->items);

        $phoneCountry = $this->getPhoneCountry($request->shippingAddress->country ?? '');

        $params = [
            'param_place_order_request4_open_api_d_t_o' => [
                'out_order_id'      => (string) $request->internalReference,
                'logistics_address' => [
                    'contact_person' => $request->shippingAddress->fullName(),
                    'phone_num'      => $request->shippingAddress->phone ?? '',
                    'mobile_no'      => $request->shippingAddress->phone ?? '',
                    'phone_country'  => $phoneCountry,
                    'address'        => $request->shippingAddress->address,
                    'city'           => $request->shippingAddress->city,
                    'province'       => $request->shippingAddress->state ?? '',
                    'zip'            => $request->shippingAddress->postcode ?? '',
                    'country'        => $request->shippingAddress->country ?? '',
                    'company_name'   => $request->shippingAddress->companyName ?? '',
                ],
                'product_items'     => $items,
            ],
        ];

        try {
            $result = $this->apiClient->call('aliexpress.ds.order.create', $token->access_token, $params);
        } catch (\Throwable $e) {
            return SupplierOrderResult::failure(
                isRetryable: true,
                code: 'API_TRANSPORT_ERROR',
                message: $e->getMessage()
            );
        }

        $body = $result['body'] ?? [];
        $code = $result['code'] !== null ? (string) $result['code'] : null;
        $message = $result['message'];

        if (! $result['ok']) {
            $isRetryable = $this->classifyErrorCode($code);

            return SupplierOrderResult::failure(
                isRetryable: $isRetryable,
                code: $code,
                message: $message,
                raw: $body
            );
        }

        // Check for business logic failure inside result
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $res = $resp['result'] ?? [];

        if (isset($res['is_success']) && ! $res['is_success']) {
            $errCode = $res['error_code'] ?? 'BUSINESS_ERROR';
            $errMsg = $res['error_msg'] ?? 'AliExpress business validation failed.';
            $isRetryable = $this->classifyErrorCode($errCode);

            return SupplierOrderResult::failure(
                isRetryable: $isRetryable,
                code: $errCode,
                message: $errMsg,
                raw: $body
            );
        }


        // Try to extract external order ID
        $externalOrderId = $this->parseExternalOrderId($body);

        if ($externalOrderId === null || $externalOrderId === '') {
            return SupplierOrderResult::failure(
                isRetryable: false,
                code: 'EXTERNAL_ORDER_ID_NOT_FOUND',
                message: 'AliExpress returned success but no order ID was found in response payload.',
                raw: $body
            );
        }

        return SupplierOrderResult::success(
            externalOrderId: $externalOrderId,
            code: $code,
            message: $message,
            raw: $body
        );
    }

    /**
     * Get the supplier order status using the external ID.
     */
    public function getSupplierOrderStatus(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderStatus
    {
        $token = $providerAccountId
            ? $this->oauthService->getTokenById($providerAccountId)
            : $this->oauthService->latestToken();

        if ($token === null) {
            return new SupplierOrderStatus(
                mappedState: PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                rawState: 'OAUTH_TOKEN_MISSING'
            );
        }

        try {
            $result = $this->apiClient->call('aliexpress.ds.order.get', $token->access_token, [
                'order_id' => $externalOrderId,
            ]);
        } catch (\Throwable $e) {
            // Transient error: return existing state or map to a temporary transient indicator
            // Since status is called inside scheduler/polling, return unchanged/null parameters
            return new SupplierOrderStatus(
                mappedState: 'failed_transient',
                rawState: 'API_TRANSPORT_ERROR: ' . $e->getMessage()
            );
        }

        if (! $result['ok']) {
            $code = $result['code'] !== null ? (string) $result['code'] : null;
            $isRetryable = $this->classifyErrorCode($code);

            return new SupplierOrderStatus(
                mappedState: $isRetryable ? 'failed_transient' : PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                rawState: 'API_ERROR_CODE: ' . ($code ?? 'UNKNOWN')
            );
        }

        $body = $result['body'] ?? [];
        $resp = $body['aliexpress_ds_order_get_response'] ?? $body;
        $res = $resp['result'] ?? [];

        $rawState = $res['order_status'] ?? null;
        $trackingNumber = $res['logistics_info_list']['logistics_info'][0]['logistics_no'] ?? null;
        $trackingCompany = $res['logistics_info_list']['logistics_info'][0]['logistics_service_name'] ?? null;

        if ($rawState === null) {
            return new SupplierOrderStatus(
                mappedState: PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                rawState: 'STATUS_MISSING_FROM_RESPONSE'
            );
        }

        $mappedState = $this->mapOrderStatus($rawState);

        return new SupplierOrderStatus(
            mappedState: $mappedState,
            rawState: (string) $rawState,
            trackingNumber: $trackingNumber !== null ? (string) $trackingNumber : null,
            trackingCompany: $trackingCompany !== null ? (string) $trackingCompany : null
        );
    }

    /**
     * Attempt to find the supplier order using our internal reference.
     */
    public function findByReference(string $internalReference, ?int $providerAccountId = null): ?string
    {
        $token = $providerAccountId
            ? $this->oauthService->getTokenById($providerAccountId)
            : $this->oauthService->latestToken();

        if ($token === null) {
            return null;
        }

        // Note: AliExpress Dropshipping Open API does not support querying directly by out_order_id
        // via a simple search endpoint. However, if findByReference is called as a best-effort,
        // we return null. If the supplier does not support it, returning null is the correct default.
        return null;
    }

    /**
     * Attempt to cancel the supplier order (best-effort).
     */
    public function cancelSupplierOrder(string $externalOrderId, ?int $providerAccountId = null): SupplierOrderResult
    {
        $token = $providerAccountId
            ? $this->oauthService->getTokenById($providerAccountId)
            : $this->oauthService->latestToken();

        if ($token === null) {
            return SupplierOrderResult::failure(
                isRetryable: false,
                code: 'OAUTH_TOKEN_MISSING',
                message: 'AliExpress OAuth token missing.'
            );
        }

        try {
            $result = $this->apiClient->call('aliexpress.ds.order.cancel', $token->access_token, [
                'aliexpress_order_id' => $externalOrderId,
            ]);

            if ($result['ok']) {
                return SupplierOrderResult::success(
                    externalOrderId: $externalOrderId,
                    code: '0',
                    message: 'Order cancelled successfully',
                    raw: $result['body']
                );
            }

            return SupplierOrderResult::failure(
                isRetryable: false,
                code: $result['code'] ?? 'CANCEL_FAILED',
                message: $result['message'] ?? 'Cancel failed',
                raw: $result['body']
            );
        } catch (\Throwable $e) {
            return SupplierOrderResult::failure(
                isRetryable: true,
                code: 'CONNECTION_ERROR',
                message: $e->getMessage()
            );
        }
    }

    /**
     * Resolve the phone country code for the destination country.
     */
    protected function getPhoneCountry(string $country): string
    {
        $map = [
            'US' => '1',
            'SA' => '966',
            'AE' => '971',
            'GB' => '44',
            'CA' => '1',
        ];

        return $map[strtoupper($country)] ?? '1';
    }


    /**
     * Classify an API error code as transient (retryable) or permanent.
     */
    protected function classifyErrorCode(?string $code): bool
    {
        if ($code === null) {
            return true; // Default to retryable if connection timeout / transport error
        }

        $transientCodes = [
            'isp.system-error',
            'aop.platform-error',
            'isp.api-remote-connection-timeout',
            'isp.api-remote-connection-error',
            'isp.api-remote-service-error',
            'isp.api-remote-read-timeout',
            'isp.api-remote-write-timeout',
            '41', // gateway request timeout
            'isp.gateway-error',
        ];

        if (in_array(strtolower($code), $transientCodes, true)) {
            return true;
        }

        // AliExpress uses numeric sub_code for business rules.
        // Let's assume general network timeouts or specific rate limits are retryable.
        if (str_contains(strtolower($code), 'timeout') || str_contains(strtolower($code), 'rate_limit') || $code === '403') {
            return true;
        }

        return false;
    }

    /**
     * Parse external order ID from AliExpress API response body.
     */
    protected function parseExternalOrderId(array $body): ?string
    {
        $resp = $body['aliexpress_ds_order_create_response'] ?? $body;
        $res = $resp['result'] ?? [];

        if (isset($res['order_list']['number'])) {
            $list = $res['order_list']['number'];
            return is_array($list) ? implode(',', $list) : (string) $list;
        }

        if (isset($res['order_id'])) {
            return (string) $res['order_id'];
        }

        if (isset($res['order_list']) && is_array($res['order_list'])) {
            $ids = [];
            foreach ($res['order_list'] as $item) {
                if (is_scalar($item)) {
                    $ids[] = $item;
                } elseif (isset($item['order_id'])) {
                    $ids[] = $item['order_id'];
                }
            }
            if ($ids !== []) {
                return implode(',', $ids);
            }
        }

        return null;
    }

    /**
     * Map raw AliExpress status to bridge PurchaseOrder state.
     */
    protected function mapOrderStatus(string $rawState): string
    {
        switch (strtoupper($rawState)) {
            case 'PLACE_ORDER_SUCCESS':
            case 'WAIT_BUYER_PAY':
                return 'awaiting_payment_to_supplier';

            case 'WAIT_SELLER_SEND_GOODS':
                return PurchaseOrder::STATE_SUBMITTED;

            case 'SELLER_SEND_GOODS':
                return PurchaseOrder::STATE_SHIPPED;

            case 'TRADE_SUCCESS':
            case 'FINISH':
                return PurchaseOrder::STATE_DELIVERED;

            case 'TRADE_CLOSED':
            case 'IN_CANCEL':
                return PurchaseOrder::STATE_CANCELED;

            default:
                return PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW;
        }
    }
}
