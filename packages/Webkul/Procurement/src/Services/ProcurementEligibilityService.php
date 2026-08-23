<?php

namespace Webkul\Procurement\Services;

use App\Models\AliExpressProductImport;
use App\Models\HigestSourceOffer;
use Webkul\Product\Models\ProductProxy;
use Webkul\Sales\Contracts\Order;
use Webkul\Sales\Contracts\OrderItem;

class ProcurementEligibilityService
{
    /**
     * Check if an order is eligible for procurement processing.
     * Confirmed prepaid orders or accepted COD orders are eligible.
     */
    public function isOrderEligible(Order $order): bool
    {
        $paymentMethod = strtolower((string) ($order->payment->method ?? ''));

        if ($paymentMethod === 'cashondelivery') {
            // For COD: check if order is confirmed/accepted
            return in_array(strtolower((string) $order->status), [
                'processing',
                'pending_fulfillment',
                'completed',
                'approved',
            ], true) || ($order->status !== 'canceled' && $order->status !== 'pending');
        }

        // For Prepaid orders: must be paid / invoice generated or processing
        return $order->invoices()->exists() || in_array(strtolower((string) $order->status), [
            'processing',
            'completed',
            'pending_fulfillment',
        ], true);
    }

    /**
     * Inspect an order item to determine its sourcing characteristics.
     *
     * @return array{
     *     is_imported: bool,
     *     provider: string,
     *     provider_account_id: int|null,
     *     supplier_store_id: string|null,
     *     supplier_store_name: string|null,
     *     supplier_product_id: string,
     *     supplier_sku_id: string,
     *     unit_cost: float,
     *     currency: string,
     *     metadata_status: string,
     *     exception_reason: string|null,
     *     source_snapshot: array
     * }
     */
    public function classifyOrderItem(OrderItem $item): array
    {
        $productId = (int) $item->product_id;
        $product = ProductProxy::modelClass()::find($productId);
        $parentProductId = $product?->parent_id ?? $productId;

        // 1. Check AliExpressProductImport record
        $import = AliExpressProductImport::where('product_id', $parentProductId)
            ->where('status', 'success')
            ->first();

        // 2. Check HigestSourceOffer record
        $offer = HigestSourceOffer::where('variant_id', $productId)
            ->where('source_provider', 'aliexpress')
            ->first();

        if (! $offer && $parentProductId !== $productId) {
            $offer = HigestSourceOffer::where('product_id', $parentProductId)
                ->where('source_provider', 'aliexpress')
                ->first();
        }

        $isImported = ($import !== null || $offer !== null);

        if (! $isImported) {
            return [
                'is_imported' => false,
                'provider' => 'internal',
                'provider_account_id' => null,
                'supplier_store_id' => null,
                'supplier_store_name' => null,
                'supplier_product_id' => '',
                'supplier_sku_id' => '',
                'unit_cost' => (float) ($product?->cost ?? 0.0),
                'currency' => 'USD',
                'metadata_status' => 'valid',
                'exception_reason' => null,
                'source_snapshot' => [
                    'source_type' => 'internal_catalog',
                    'sku' => $item->sku,
                    'name' => $item->name,
                ],
            ];
        }

        $supplierProductId = (string) ($import?->aliexpress_product_id ?? $offer?->source_sku_id ?? 'ae_prod_'.$parentProductId);
        $supplierSkuId = (string) ($offer?->source_sku_id ?? $item->additional['supplier_sku_id'] ?? $item->sku ?? 'ae_sku_'.$productId);

        $payload = $import?->payload_snapshot ?? [];
        $payloadStoreId = ! empty($payload['store_info']['store_id']) ? (string) $payload['store_info']['store_id'] : (! empty($payload['store_id']) ? (string) $payload['store_id'] : null);
        $payloadStoreName = ! empty($payload['store_info']['store_name']) ? (string) $payload['store_info']['store_name'] : (! empty($payload['store_name']) ? (string) $payload['store_name'] : null);

        $additionalStoreId = ! empty($item->additional['supplier_store_id']) ? (string) $item->additional['supplier_store_id'] : null;
        $additionalStoreName = ! empty($item->additional['supplier_store_name']) ? (string) $item->additional['supplier_store_name'] : null;

        // Resolve store provenance and detect conflict or missing metadata
        $supplierStoreId = null;
        $supplierStoreName = null;
        $provenanceSource = null;
        $metadataStatus = 'valid';
        $exceptionReason = null;

        if ($payloadStoreId !== null && $additionalStoreId !== null && $payloadStoreId !== $additionalStoreId) {
            $metadataStatus = 'conflicting_metadata';
            $exceptionReason = 'CONFLICTING_SUPPLIER_METADATA';
            $supplierStoreId = null;
            $supplierStoreName = null;
            $provenanceSource = 'conflicting';
        } elseif ($payloadStoreId !== null) {
            $supplierStoreId = $payloadStoreId;
            $supplierStoreName = $payloadStoreName ?: 'Store #'.$payloadStoreId;
            $provenanceSource = 'import_payload_snapshot';
        } elseif ($additionalStoreId !== null) {
            $supplierStoreId = $additionalStoreId;
            $supplierStoreName = $additionalStoreName ?: 'Store #'.$additionalStoreId;
            $provenanceSource = 'order_item_additional';
        } else {
            $metadataStatus = 'missing_store';
            $exceptionReason = 'MISSING_SUPPLIER_STORE_METADATA';
            $supplierStoreId = null;
            $supplierStoreName = null;
            $provenanceSource = 'missing';
        }

        $unitCost = (float) ($offer?->acquisition_cost ?? $item->additional['supplier_unit_cost'] ?? $product?->cost ?? 10.0);
        $currency = strtoupper((string) ($offer?->source_currency ?? $import?->shipping_currency ?? 'USD'));

        return [
            'is_imported' => true,
            'provider' => 'aliexpress',
            'provider_account_id' => $payload['provider_account_id'] ?? null,
            'supplier_store_id' => $supplierStoreId,
            'supplier_store_name' => $supplierStoreName,
            'supplier_product_id' => $supplierProductId,
            'supplier_sku_id' => $supplierSkuId,
            'unit_cost' => $unitCost,
            'currency' => $currency,
            'metadata_status' => $metadataStatus,
            'exception_reason' => $exceptionReason,
            'source_snapshot' => [
                'import_id' => $import?->id,
                'offer_id' => $offer?->id,
                'aliexpress_product_id' => $supplierProductId,
                'supplier_sku_id' => $supplierSkuId,
                'supplier_store_id' => $supplierStoreId,
                'supplier_store_name' => $supplierStoreName,
                'store_provenance' => [
                    'source' => $provenanceSource,
                    'payload_store_id' => $payloadStoreId,
                    'additional_store_id' => $additionalStoreId,
                    'resolved_store_id' => $supplierStoreId,
                ],
                'metadata_status' => $metadataStatus,
                'exception_reason' => $exceptionReason,
                'unit_cost' => $unitCost,
                'currency' => $currency,
                'imported_at' => $import?->created_at?->toIso8601String(),
            ],
        ];
    }
}
