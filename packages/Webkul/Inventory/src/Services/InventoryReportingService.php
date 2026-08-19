<?php

namespace Webkul\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReportingService
{
    /**
     * 1. Comprehensive Movements Report.
     */
    public function getMovementsReport(array $filters = []): Collection
    {
        $query = DB::table('inventory_movements')
            ->leftJoin('inventory_sources as src', 'inventory_movements.source_inventory_source_id', '=', 'src.id')
            ->leftJoin('inventory_sources as trg', 'inventory_movements.target_inventory_source_id', '=', 'trg.id')
            ->leftJoin('admins', 'inventory_movements.actor_id', '=', 'admins.id')
            ->select(
                'inventory_movements.id',
                'inventory_movements.movement_type',
                'inventory_movements.sku',
                'inventory_movements.quantity',
                'src.name as source_name',
                'trg.name as target_name',
                'inventory_movements.order_id',
                'inventory_movements.purchase_order_id',
                'inventory_movements.reference_event',
                DB::raw('COALESCE(admins.name, inventory_movements.actor_type) as actor'),
                'inventory_movements.created_at'
            )
            ->orderBy('inventory_movements.id', 'desc');

        if (! empty($filters['date_from'])) {
            $query->where('inventory_movements.created_at', '>=', $filters['date_from'].' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $query->where('inventory_movements.created_at', '<=', $filters['date_to'].' 23:59:59');
        }
        if (! empty($filters['sku'])) {
            $query->where('inventory_movements.sku', 'like', "%{$filters['sku']}%");
        }
        if (! empty($filters['movement_type'])) {
            $query->where('inventory_movements.movement_type', $filters['movement_type']);
        }

        return $query->limit(200)->get();
    }

    /**
     * 2. Balances by Source Report (Owned Inventory Sources).
     * Excludes external / legacy sources (default, aliexpress_source).
     */
    public function getSourcesBalanceReport(array $filters = []): Collection
    {
        $query = DB::table('inventory_sources')
            ->leftJoin('product_inventories', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
            ->select(
                'inventory_sources.id',
                'inventory_sources.code',
                'inventory_sources.name',
                'inventory_sources.country',
                'inventory_sources.source_type',
                'inventory_sources.is_salable',
                'inventory_sources.is_delivery_source',
                DB::raw('COUNT(DISTINCT product_inventories.product_id) as total_skus'),
                DB::raw('COALESCE(SUM(product_inventories.qty), 0) as total_quantity')
            );

        if (empty($filters['include_external'])) {
            $query->whereNotIn('inventory_sources.code', ['default', 'aliexpress_source']);
        }

        return $query->groupBy(
            'inventory_sources.id',
            'inventory_sources.code',
            'inventory_sources.name',
            'inventory_sources.country',
            'inventory_sources.source_type',
            'inventory_sources.is_salable',
            'inventory_sources.is_delivery_source'
        )->get();
    }

    /**
     * 3. Transfer Manifests Report.
     */
    public function getTransfersReport(array $filters = []): Collection
    {
        $query = DB::table('inventory_transfer_manifests')
            ->leftJoin('inventory_sources as src', 'inventory_transfer_manifests.source_inventory_source_id', '=', 'src.id')
            ->leftJoin('inventory_sources as dest', 'inventory_transfer_manifests.destination_inventory_source_id', '=', 'dest.id')
            ->select(
                'inventory_transfer_manifests.id',
                'inventory_transfer_manifests.manifest_number',
                'src.name as source_name',
                'dest.name as destination_name',
                'inventory_transfer_manifests.status',
                'inventory_transfer_manifests.carrier_name',
                'inventory_transfer_manifests.tracking_number',
                'inventory_transfer_manifests.total_packages',
                'inventory_transfer_manifests.total_items_count',
                'inventory_transfer_manifests.dispatched_at',
                'inventory_transfer_manifests.received_at',
                'inventory_transfer_manifests.created_at'
            )
            ->orderBy('inventory_transfer_manifests.id', 'desc');

        if (! empty($filters['status'])) {
            $query->where('inventory_transfer_manifests.status', $filters['status']);
        }

        return $query->limit(200)->get();
    }

    /**
     * 4. Inbound Receipts & Discrepancies Report.
     */
    public function getReceiptsDiscrepanciesReport(array $filters = []): Collection
    {
        $query = DB::table('inbound_receipt_manifests')
            ->leftJoin('inventory_sources as dest', 'inbound_receipt_manifests.destination_inventory_source_id', '=', 'dest.id')
            ->leftJoin('inventory_transfer_manifests as trf', 'inbound_receipt_manifests.inventory_transfer_manifest_id', '=', 'trf.id')
            ->select(
                'inbound_receipt_manifests.id',
                'inbound_receipt_manifests.receipt_number',
                'trf.manifest_number as transfer_manifest_number',
                'dest.name as destination_name',
                'inbound_receipt_manifests.status',
                'inbound_receipt_manifests.total_received_good',
                'inbound_receipt_manifests.total_received_damaged',
                'inbound_receipt_manifests.total_received_missing',
                'inbound_receipt_manifests.created_at'
            )
            ->orderBy('inbound_receipt_manifests.id', 'desc');

        return $query->limit(200)->get();
    }

    /**
     * 5. Order Allocation Report.
     */
    public function getAllocationsReport(array $filters = []): Collection
    {
        $query = DB::table('order_allocations')
            ->leftJoin('orders', 'order_allocations.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_allocations.product_id', '=', 'products.id')
            ->select(
                'order_allocations.id',
                'order_allocations.order_id',
                'orders.increment_id as order_increment_id',
                'products.sku',
                'order_allocations.allocation_type',
                'order_allocations.source_code',
                'order_allocations.reserved_qty',
                'order_allocations.fulfilled_qty',
                'order_allocations.state',
                'order_allocations.created_at'
            )
            ->orderBy('order_allocations.id', 'desc');

        return $query->limit(200)->get();
    }

    /**
     * 6. Audit Reconciliation Report (Owned Inventory Balance vs Movement Ledger).
     * Excludes external / legacy sources (default, aliexpress_source).
     */
    public function getReconciliationReport(array $filters = []): Collection
    {
        $products = DB::table('products')->limit(50)->get();
        $sources = DB::table('inventory_sources')
            ->whereNotIn('code', ['default', 'aliexpress_source'])
            ->get();

        $rows = [];

        foreach ($products as $product) {
            foreach ($sources as $source) {
                // Actual table stock
                $actualStock = (int) DB::table('product_inventories')
                    ->where('product_id', $product->id)
                    ->where('inventory_source_id', $source->id)
                    ->value('qty');

                // Sum of ledger movements
                $inflow = (int) DB::table('inventory_movements')
                    ->where('product_id', $product->id)
                    ->where('target_inventory_source_id', $source->id)
                    ->sum('quantity');

                $outflow = (int) DB::table('inventory_movements')
                    ->where('product_id', $product->id)
                    ->where('source_inventory_source_id', $source->id)
                    ->sum('quantity');

                $ledgerCalculated = $inflow + $outflow;

                if ($actualStock > 0 || $ledgerCalculated > 0) {
                    $rows[] = (object) [
                        'sku' => $product->sku,
                        'source_code' => $source->code,
                        'source_name' => $source->name,
                        'actual_stock' => $actualStock,
                        'ledger_stock' => $ledgerCalculated,
                        'difference' => $actualStock - $ledgerCalculated,
                        'status' => ($actualStock == $ledgerCalculated) ? 'Matched' : 'Discrepancy',
                    ];
                }
            }
        }

        return collect($rows);
    }

    /**
     * 7. Unclassified Products Report.
     */
    public function getUnclassifiedProductsReport(array $filters = []): Collection
    {
        return DB::table('products')
            ->leftJoin('product_inventories', 'products.id', '=', 'product_inventories.product_id')
            ->select(
                'products.id',
                'products.sku',
                'products.type',
                DB::raw("COALESCE((SELECT pav.text_value FROM product_attribute_values pav JOIN attributes a ON pav.attribute_id = a.id WHERE pav.product_id = products.id AND a.code = 'origin_type' LIMIT 1), 'unclassified') as origin_type"),
                DB::raw('COALESCE(SUM(product_inventories.qty), 0) as total_stock'),
                'products.created_at'
            )
            ->groupBy('products.id', 'products.sku', 'products.type', 'products.created_at')
            ->having('origin_type', '=', 'unclassified')
            ->limit(100)
            ->get();
    }

    /**
     * 8. Independent Legacy / External Exception Report.
     * Audits records associated with 'default' without polluting financial inventory metrics.
     */
    public function getLegacyExceptionReport(array $filters = []): Collection
    {
        $defaultSource = DB::table('inventory_sources')->where('code', 'default')->first();

        if (! $defaultSource) {
            return collect();
        }

        return DB::table('product_inventories')
            ->join('products', 'product_inventories.product_id', '=', 'products.id')
            ->select(
                'products.id as product_id',
                'products.sku',
                'products.type',
                'product_inventories.qty as legacy_quantity',
                'products.created_at',
                'products.updated_at',
                DB::raw("'default' as source_code"),
                DB::raw("COALESCE((SELECT pav.text_value FROM product_attribute_values pav JOIN attributes a ON pav.attribute_id = a.id WHERE pav.product_id = products.id AND a.code = 'origin_type' LIMIT 1), 'legacy') as origin_type")
            )
            ->where('product_inventories.inventory_source_id', $defaultSource->id)
            ->get();
    }
}
