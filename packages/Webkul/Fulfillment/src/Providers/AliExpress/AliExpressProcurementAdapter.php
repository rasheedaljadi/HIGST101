<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Webkul\Fulfillment\Contracts\ExternalFulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;

class AliExpressProcurementAdapter implements ExternalFulfillmentProviderInterface
{
    public function __construct(protected AliExpressHttpClient $client) {}

    public function code(): string
    {
        return 'aliexpress';
    }

    public function isConfigured(int $providerAccountId): bool
    {
        $account = \Webkul\Fulfillment\Models\ProviderAccount::find($providerAccountId);
        return $account !== null && $account->status === 'ACTIVE' && ! empty($account->access_token);
    }

    public function createSupplierOrder(
        SupplierOrderRequest $request,
        string $contractVersion
    ): SupplierOrderResult {
        $account = \Webkul\Fulfillment\Models\ProviderAccount::findOrFail($request->providerAccountId);

        $items = [];
        foreach ($request->items as $line) {
            $items[] = [
                'product_id' => $line->aliexpressProductId,
                'sku_id'     => $line->skuId,
                'quantity'   => $line->qty,
            ];
        }

        $params = [
            'out_order_id'     => $request->internalReference,
            'items'            => $items,
            'shipping_address' => [
                'contact_person' => $request->shippingAddress->firstName . ' ' . $request->shippingAddress->lastName,
                'address'        => $request->shippingAddress->address,
                'city'           => $request->shippingAddress->city,
                'province'       => $request->shippingAddress->state,
                'zip'            => $request->shippingAddress->postcode,
                'country'        => $request->shippingAddress->country,
                'phone'          => $request->shippingAddress->phone,
            ]
        ];

        $meta = [
            'correlation_id'        => $request->idempotencyKey,
            'causation_id'          => $request->idempotencyKey,
            'provider_account_id'   => $account->id,
            'idempotency_key'       => $request->idempotencyKey,
            'api_version'           => 'v2',
            'provider_api_version'  => '2026-06',
        ];

        try {
            $response = $this->client->callResilient(
                'aliexpress.ds.order.create',
                $account->access_token,
                $params,
                $meta
            );

            if ($response['ok']) {
                $extOrderId = $response['body']['aliexpress_order_id'] ?? null;
                if (! $extOrderId) {
                    return SupplierOrderResult::failure(
                        isRetryable: false,
                        code: 'MISSING_ORDER_ID',
                        message: 'API returned success but no external order ID was found.',
                        raw: $response['body']
                    );
                }

                return SupplierOrderResult::success(
                    externalOrderId: (string) $extOrderId,
                    code: '0',
                    message: 'Order created successfully.',
                    raw: $response['body']
                );
            }

            $isRetryable = in_array((string) $response['code'], ['500', '10001', 'RateLimitExceeded'], true);
            return SupplierOrderResult::failure(
                isRetryable: $isRetryable,
                code: (string) $response['code'],
                message: $response['message'] ?? 'Error creating order',
                raw: $response['body'] ?? null
            );

        } catch (\Throwable $e) {
            return SupplierOrderResult::failure(
                isRetryable: true,
                code: 'CONNECTION_ERROR',
                message: $e->getMessage()
            );
        }
    }

    public function getSupplierOrderStatus(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion
    ): SupplierOrderStatus {
        $account = \Webkul\Fulfillment\Models\ProviderAccount::findOrFail($providerAccountId);

        $params = [
            'aliexpress_order_id' => $externalOrderId,
        ];

        $meta = [
            'correlation_id'       => uniqid('sync-', true),
            'causation_id'         => $externalOrderId,
            'provider_account_id'  => $providerAccountId,
            'api_version'          => 'v2',
            'provider_api_version' => '2026-06',
        ];

        try {
            $response = $this->client->callResilient(
                'aliexpress.ds.order.get',
                $account->access_token,
                $params,
                $meta
            );

            if ($response['ok']) {
                $data = $response['body']['order_info'] ?? [];
                $rawState = $data['status'] ?? 'unknown';

                $mappedState = match (strtolower($rawState)) {
                    'wait_send'       => 'PROCESSING',
                    'wait_receive', 'shipped' => 'SHIPPED',
                    'finish', 'completed'   => 'COMPLETED',
                    'cancelled', 'closed' => 'CANCELLED',
                    default           => 'PROCESSING',
                };

                return new SupplierOrderStatus(
                    mappedState: $mappedState,
                    rawState: $rawState,
                    trackingNumber: $data['tracking_number'] ?? null,
                    trackingCompany: $data['carrier'] ?? null
                );
            }

            return new SupplierOrderStatus('PROCESSING', 'error_fetching');
        } catch (\Throwable $e) {
            return new SupplierOrderStatus('PROCESSING', 'exception_thrown');
        }
    }

    public function cancelSupplierOrder(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion
    ): SupplierOrderResult {
        $account = \Webkul\Fulfillment\Models\ProviderAccount::findOrFail($providerAccountId);

        $params = [
            'aliexpress_order_id' => $externalOrderId,
        ];

        $meta = [
            'correlation_id'       => uniqid('cancel-', true),
            'causation_id'         => $externalOrderId,
            'provider_account_id'  => $providerAccountId,
            'api_version'          => 'v2',
            'provider_api_version' => '2026-06',
        ];

        try {
            $response = $this->client->callResilient(
                'aliexpress.ds.order.cancel',
                $account->access_token,
                $params,
                $meta
            );

            if ($response['ok']) {
                return SupplierOrderResult::success(
                    externalOrderId: $externalOrderId,
                    code: '0',
                    message: 'Order cancelled successfully',
                    raw: $response['body']
                );
            }

            return SupplierOrderResult::failure(
                isRetryable: false,
                code: (string) $response['code'],
                message: $response['message'] ?? 'Cancel failed',
                raw: $response['body']
            );
        } catch (\Throwable $e) {
            return SupplierOrderResult::failure(
                isRetryable: true,
                code: 'CONNECTION_ERROR',
                message: $e->getMessage()
            );
        }
    }

    public function findByReference(
        string $internalReference,
        int $providerAccountId,
        string $contractVersion
    ): ?string {
        $account = \Webkul\Fulfillment\Models\ProviderAccount::findOrFail($providerAccountId);

        $params = [
            'out_order_id' => $internalReference,
        ];

        $meta = [
            'correlation_id'       => uniqid('reconcile-', true),
            'causation_id'         => $internalReference,
            'provider_account_id'  => $providerAccountId,
            'api_version'          => 'v2',
            'provider_api_version' => '2026-06',
        ];

        try {
            $response = $this->client->callResilient(
                'aliexpress.ds.order.find_by_reference',
                $account->access_token,
                $params,
                $meta
            );

            if ($response['ok']) {
                return $response['body']['aliexpress_order_id'] ?? null;
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
