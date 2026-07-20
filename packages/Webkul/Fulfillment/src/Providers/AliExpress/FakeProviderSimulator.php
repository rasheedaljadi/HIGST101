<?php

namespace Webkul\Fulfillment\Providers\AliExpress;

use Webkul\Fulfillment\Contracts\ExternalFulfillmentProviderInterface;
use Webkul\Fulfillment\DataObjects\SupplierOrderRequest;
use Webkul\Fulfillment\DataObjects\SupplierOrderResult;
use Webkul\Fulfillment\DataObjects\SupplierOrderStatus;

class FakeProviderSimulator implements ExternalFulfillmentProviderInterface
{
    protected ?string $simulatedFailureMode = 'NORMAL';

    public function code(): string
    {
        return 'aliexpress_simulator';
    }

    public function isConfigured(int $providerAccountId): bool
    {
        return true;
    }

    public function setFailureMode(?string $mode): void
    {
        $this->simulatedFailureMode = strtoupper($mode ?? 'NORMAL');
    }

    public function createSupplierOrder(
        SupplierOrderRequest $request,
        string $contractVersion = '1.0'
    ): SupplierOrderResult {
        $mode = $this->simulatedFailureMode;

        if ($mode === 'HIGH_LATENCY') {
            usleep(100000); // 100ms artificial latency
        }

        if ($mode === 'RATE_LIMIT') {
            return SupplierOrderResult::failure(
                isRetryable: true,
                code: 'RATE_LIMIT',
                message: 'Rate limit exceeded for AliExpress API',
                raw: ['error' => 'Rate limit exceeded']
            );
        }

        if ($mode === 'NETWORK_TIMEOUT' || $mode === 'TIMEOUT') {
            throw new \RuntimeException("Simulator timeout error.");
        }

        if ($mode === 'OUT_OF_STOCK' || $mode === 'STOCKOUT') {
            return SupplierOrderResult::failure(
                isRetryable: false,
                code: 'STOCKOUT',
                message: 'Items are out of stock',
                raw: ['error' => 'Stockout']
            );
        }

        if ($mode === 'PRICE_CHANGED') {
            return SupplierOrderResult::failure(
                isRetryable: false,
                code: 'PRICE_CHANGED',
                message: 'Supplier price has changed',
                raw: ['error' => 'Price changed']
            );
        }

        if ($mode === 'PARTIAL_SUCCESS') {
            return SupplierOrderResult::success(
                externalOrderId: "SIM-EXT-PARTIAL-123",
                code: '0',
                message: 'Simulated Order Partial Success',
                raw: [
                    'aliexpress_order_id' => 'SIM-EXT-PARTIAL-123',
                    'status'              => 'PLACE_ORDER_PARTIAL_SUCCESS',
                    'order_amount'        => 22.99,
                    'currency'            => 'USD'
                ]
            );
        }

        $externalOrderId = "SIM-EXT-100200300";
        return SupplierOrderResult::success(
            externalOrderId: $externalOrderId,
            code: '0',
            message: 'Simulated Order Success',
            raw: [
                'aliexpress_order_id' => $externalOrderId,
                'status'              => 'PLACE_ORDER_SUCCESS',
                'order_amount'        => 45.99,
                'currency'            => 'USD'
            ]
        );
    }

    public function getSupplierOrderStatus(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion = '1.0'
    ): SupplierOrderStatus {
        $mode = $this->simulatedFailureMode;

        if ($mode === 'LOST_TRACKING') {
            return new SupplierOrderStatus(
                mappedState: 'PROCESSING',
                rawState: 'wait_send',
                trackingNumber: null,
                trackingCompany: null
            );
        }

        if ($mode === 'SHIPPED') {
            return new SupplierOrderStatus(
                mappedState: 'SHIPPED',
                rawState: 'wait_receive',
                trackingNumber: 'TRK-SIM-999',
                trackingCompany: 'AliExpress Standard'
            );
        }

        return new SupplierOrderStatus(
            mappedState: 'COMPLETED',
            rawState: 'finish',
            trackingNumber: 'TRK-SIM-999',
            trackingCompany: 'AliExpress Standard'
        );
    }

    public function cancelSupplierOrder(
        string $externalOrderId,
        int $providerAccountId,
        string $contractVersion = '1.0'
    ): SupplierOrderResult {
        return SupplierOrderResult::success(
            externalOrderId: $externalOrderId,
            code: 'CANCEL_SUCCESS',
            message: 'Simulated Cancel Order Success',
            raw: ['status' => 'CANCELLED']
        );
    }

    public function findByReference(
        string $internalReference,
        int $providerAccountId,
        string $contractVersion = '1.0'
    ): ?string {
        if ($this->simulatedFailureMode === 'RECONCILE_MATCH' || $this->simulatedFailureMode === 'NORMAL') {
            return "SIM-EXT-100200300";
        }
        return null;
    }
}
