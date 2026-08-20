<?php

namespace Webkul\Sales\Services\Lifecycle;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class OrderLifecycleStageResolver
{
    /**
     * Readiness rank map for stage progression comparison.
     */
    public const STAGE_RANKS = [
        'new' => 1,
        'payment_pending' => 2,
        'sourcing_required' => 3,
        'po_created' => 4,
        'supplier_shipped' => 5,
        'sa_received' => 6,
        'ye_in_transit' => 7,
        'ye_received' => 8,
        'confirmed' => 9,
        'handed_off' => 10,
        'delivered' => 11,
    ];

    /**
     * Resolve the stage code for an individual OrderItem based strictly on DB facts.
     */
    public function resolveItemStage(OrderItem $item, ?Order $order = null): array
    {
        $order = $order ?? $item->order;
        $additional = is_array($item->additional) ? $item->additional : json_decode($item->additional ?? '{}', true);

        // Determine item origin type
        $isImported = isset($additional['aliexpress'])
            || isset($additional['ae_product_id'])
            || (isset($additional['inventory_source']) && $additional['inventory_source'] === 'aliexpress_source');

        $originType = $isImported ? 'imported' : 'internal';
        $defaultSourceType = $isImported ? 'hayest_dropship_ye' : 'hayest_internal_ye';

        // Check 1: Exception - Canceled Order / Item
        if ($order->status === 'canceled' || $item->qty_canceled >= $item->qty_ordered) {
            return [
                'origin_type' => $originType,
                'current_stage_code' => 'new',
                'source_type' => $defaultSourceType,
                'is_exception' => true,
                'exception_reason' => 'canceled',
                'rank' => 0,
            ];
        }

        // Check 2: Exception - Delivery Failed or Returned
        $deliveryException = $this->checkDeliveryException($order->id, $originType, $defaultSourceType);
        if ($deliveryException) {
            return $deliveryException;
        }

        // Stage 1: New
        if ($order->status === 'pending') {
            return [
                'origin_type' => $originType,
                'current_stage_code' => 'new',
                'source_type' => $defaultSourceType,
                'is_exception' => false,
                'exception_reason' => null,
                'rank' => self::STAGE_RANKS['new'],
            ];
        }

        // Stage 2: Payment Pending
        if ($order->status === 'pending_payment') {
            return [
                'origin_type' => $originType,
                'current_stage_code' => 'payment_pending',
                'source_type' => $defaultSourceType,
                'is_exception' => false,
                'exception_reason' => null,
                'rank' => self::STAGE_RANKS['payment_pending'],
            ];
        }

        // Check 3: Delivery Status (Delivered / Handed Off)
        $deliveryStage = $this->checkDeliveryStage($order->id, $isImported, $item);
        if ($deliveryStage) {
            return array_merge([
                'origin_type' => $originType,
                'is_exception' => false,
                'exception_reason' => null,
            ], $deliveryStage);
        }

        // For Internal Items (Ready locally in hayest_internal_ye)
        if (! $isImported) {
            return [
                'origin_type' => 'internal',
                'current_stage_code' => 'confirmed',
                'source_type' => 'hayest_internal_ye',
                'is_exception' => false,
                'exception_reason' => null,
                'rank' => self::STAGE_RANKS['confirmed'],
            ];
        }

        // For Imported Items: Check procurement & logistics pipeline
        $importedStage = $this->resolveImportedLogisticsStage($item, $order);

        return array_merge([
            'origin_type' => 'imported',
            'is_exception' => false,
            'exception_reason' => null,
        ], $importedStage);
    }

    /**
     * Check for delivery exception states (failed, returned).
     */
    protected function checkDeliveryException(int $orderId, string $originType, string $defaultSourceType): ?array
    {
        if (! Schema::hasTable('delivery_assignments')) {
            return null;
        }

        $assignment = DB::table('delivery_assignments')->where('order_id', $orderId)->first();

        if (! $assignment) {
            return null;
        }

        if ($assignment->status === 'failed') {
            return [
                'origin_type' => $originType,
                'current_stage_code' => 'handed_off',
                'source_type' => $defaultSourceType,
                'is_exception' => true,
                'exception_reason' => 'delivery_failed',
                'rank' => 0,
            ];
        }

        if ($assignment->status === 'returned') {
            return [
                'origin_type' => $originType,
                'current_stage_code' => 'handed_off',
                'source_type' => 'hayest_quarantine_ye',
                'is_exception' => true,
                'exception_reason' => 'returned',
                'rank' => 0,
            ];
        }

        return null;
    }

    /**
     * Check delivery assignment for Order.
     * Handed off requires local allocation and valid handoff status.
     * Delivered is field delivery success, independent of COD settlement.
     */
    protected function checkDeliveryStage(int $orderId, bool $isImported, OrderItem $item): ?array
    {
        if (! Schema::hasTable('delivery_assignments')) {
            return null;
        }

        $assignment = DB::table('delivery_assignments')->where('order_id', $orderId)->first();

        if (! $assignment) {
            return null;
        }

        // Stage 11: Delivered (Field delivery confirmed)
        if ($assignment->status === 'delivered') {
            return [
                'current_stage_code' => 'delivered',
                'source_type' => $isImported ? 'hayest_dropship_ye' : 'hayest_internal_ye',
                'rank' => self::STAGE_RANKS['delivered'],
            ];
        }

        // Stage 10: Handed Off (Executing assignment after local Yemen receipt)
        if (in_array($assignment->status, ['assigned', 'picked_up', 'out_for_delivery'])) {
            // For imported items, confirm Yemen reception before handoff
            if ($isImported) {
                $isYemenReceived = $this->isImportedYemenReceived($item);
                if (! $isYemenReceived) {
                    return null; // Cannot jump to handed_off before Yemen reception
                }
            }

            return [
                'current_stage_code' => 'handed_off',
                'source_type' => $isImported ? 'hayest_dropship_ye' : 'hayest_internal_ye',
                'rank' => self::STAGE_RANKS['handed_off'],
            ];
        }

        return null;
    }

    /**
     * Helper to verify imported item reception in Yemen.
     */
    protected function isImportedYemenReceived(OrderItem $item): bool
    {
        if (Schema::hasTable('inventory_transfer_manifests') && Schema::hasTable('inventory_transfer_manifest_items')) {
            return DB::table('inventory_transfer_manifest_items')
                ->join('inventory_transfer_manifests', 'inventory_transfer_manifest_items.inventory_transfer_manifest_id', '=', 'inventory_transfer_manifests.id')
                ->where(function ($q) use ($item) {
                    $q->where('inventory_transfer_manifest_items.order_item_id', $item->id)
                        ->orWhere('inventory_transfer_manifest_items.sku', $item->sku);
                })
                ->where('inventory_transfer_manifests.status', 'completed')
                ->exists();
        }

        return false;
    }

    /**
     * Resolve imported logistics pipeline stages.
     */
    protected function resolveImportedLogisticsStage(OrderItem $item, Order $order): array
    {
        // 1. Check Yemen Inbound Transfer Manifest
        if (Schema::hasTable('inventory_transfer_manifests') && Schema::hasTable('inventory_transfer_manifest_items')) {
            $yeTransfer = DB::table('inventory_transfer_manifest_items')
                ->join('inventory_transfer_manifests', 'inventory_transfer_manifest_items.inventory_transfer_manifest_id', '=', 'inventory_transfer_manifests.id')
                ->where(function ($q) use ($item) {
                    $q->where('inventory_transfer_manifest_items.order_item_id', $item->id)
                        ->orWhere('inventory_transfer_manifest_items.sku', $item->sku);
                })
                ->orderBy('inventory_transfer_manifests.id', 'desc')
                ->first();

            if ($yeTransfer) {
                if ($yeTransfer->status === 'completed') {
                    return [
                        'current_stage_code' => 'ye_received',
                        'source_type' => 'hayest_dropship_ye',
                        'rank' => self::STAGE_RANKS['ye_received'],
                    ];
                }

                if ($yeTransfer->status === 'in_transit') {
                    return [
                        'current_stage_code' => 'ye_in_transit',
                        'source_type' => 'hayest_dropship_sa',
                        'rank' => self::STAGE_RANKS['ye_in_transit'],
                    ];
                }
            }
        }

        // 2. Check Saudi Inbound Receipt Manifest
        if (Schema::hasTable('inbound_receipt_manifests') && Schema::hasTable('inbound_receipt_manifest_items')) {
            $saReceipt = DB::table('inbound_receipt_manifest_items')
                ->join('inbound_receipt_manifests', 'inbound_receipt_manifest_items.inbound_receipt_manifest_id', '=', 'inbound_receipt_manifests.id')
                ->where(function ($q) use ($item) {
                    $q->where('inbound_receipt_manifest_items.order_item_id', $item->id)
                        ->orWhere('inbound_receipt_manifest_items.sku', $item->sku);
                })
                ->where('inbound_receipt_manifests.status', 'completed')
                ->first();

            if ($saReceipt) {
                return [
                    'current_stage_code' => 'sa_received',
                    'source_type' => 'hayest_dropship_sa',
                    'rank' => self::STAGE_RANKS['sa_received'],
                ];
            }
        }

        // 3. Check Purchase Order
        if (Schema::hasTable('purchase_orders') && Schema::hasTable('purchase_order_items')) {
            $poItem = DB::table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->where(function ($q) use ($item, $order) {
                    $q->where('purchase_order_items.order_item_id', $item->id)
                        ->orWhere('purchase_orders.order_id', $order->id);
                })
                ->select('purchase_orders.state', 'purchase_orders.tracking_number')
                ->first();

            if ($poItem) {
                if ($poItem->state === 'shipped' || ! empty($poItem->tracking_number)) {
                    return [
                        'current_stage_code' => 'supplier_shipped',
                        'source_type' => 'aliexpress_source',
                        'rank' => self::STAGE_RANKS['supplier_shipped'],
                    ];
                }

                return [
                    'current_stage_code' => 'po_created',
                    'source_type' => 'aliexpress_source',
                    'rank' => self::STAGE_RANKS['po_created'],
                ];
            }
        }

        // Default for unallocated imported item
        return [
            'current_stage_code' => 'sourcing_required',
            'source_type' => 'aliexpress_source',
            'rank' => self::STAGE_RANKS['sourcing_required'],
        ];
    }
}
