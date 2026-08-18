<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Models\InventoryMovement;

class InventoryDashboardController extends Controller
{
    /**
     * Display Hayest Inventory Dashboard.
     */
    public function index(Request $request)
    {
        // 1. Fetch Source IDs
        $sources = DB::table('inventory_sources')
            ->select('id', 'code', 'name', 'source_type', 'is_salable', 'is_delivery_source')
            ->get()
            ->keyBy('code');

        $internalYeId = $sources->get('hayest_internal_ye')?->id;
        $dropshipYeId = $sources->get('hayest_dropship_ye')?->id;
        $dropshipSaId = $sources->get('hayest_dropship_sa')?->id;
        $quarantineSaId = $sources->get('hayest_quarantine_sa')?->id;
        $quarantineYeId = $sources->get('hayest_quarantine_ye')?->id;
        $aliExpressId = $sources->get('aliexpress_source')?->id;

        // 2. Compute Physical Balances
        $internalYeStock = $internalYeId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $internalYeId)->sum('qty')
            : 0;

        $dropshipYeStock = $dropshipYeId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $dropshipYeId)->sum('qty')
            : 0;

        $stagingSaStock = $dropshipSaId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $dropshipSaId)->sum('qty')
            : 0;

        $quarantineSaStock = $quarantineSaId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $quarantineSaId)->sum('qty')
            : 0;

        $quarantineYeStock = $quarantineYeId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $quarantineYeId)->sum('qty')
            : 0;

        $quarantineTotal = $quarantineSaStock + $quarantineYeStock;

        // Virtual Projection (isolated)
        $virtualProjectionStock = $aliExpressId
            ? (int) DB::table('product_inventories')->where('inventory_source_id', $aliExpressId)->sum('qty')
            : 0;

        // Total Salable Yemen Physical Stock
        $totalSalablePhysical = $internalYeStock + $dropshipYeStock;

        // Active Order Allocations (Reserved)
        $allocatedTotal = (int) DB::table('order_allocations')
            ->where('state', 'reserved')
            ->sum('reserved_qty');

        // In-Transit Transfers
        $inTransitCount = (int) DB::table('inventory_transfer_manifests')
            ->where('status', 'in_transit')
            ->count();

        $inTransitItemsQty = (int) DB::table('inventory_transfer_manifest_items')
            ->join('inventory_transfer_manifests', 'inventory_transfer_manifest_items.inventory_transfer_manifest_id', '=', 'inventory_transfer_manifests.id')
            ->where('inventory_transfer_manifests.status', 'in_transit')
            ->sum(DB::raw('inventory_transfer_manifest_items.qty_shipped - (inventory_transfer_manifest_items.qty_received_good + inventory_transfer_manifest_items.qty_received_damaged + inventory_transfer_manifest_items.qty_received_missing)'));

        // Receipt Discrepancies
        $totalDamagedReceived = (int) DB::table('inbound_receipt_manifests')->sum('total_received_damaged');
        $totalMissingReceived = (int) DB::table('inbound_receipt_manifests')->sum('total_received_missing');
        $discrepanciesTotal = $totalDamagedReceived + $totalMissingReceived;

        // Stalled / Interventions Required
        $stalledTransfers = DB::table('inventory_transfer_manifests')->where('status', 'discrepancy')->count();
        $stalledReceipts = DB::table('inbound_receipt_manifests')->where(function ($q) {
            $q->where('total_received_damaged', '>', 0)->orWhere('total_received_missing', '>', 0);
        })->count();
        $stalledTotal = $stalledTransfers + $stalledReceipts;

        $stats = [
            'total_salable' => $totalSalablePhysical,
            'internal_ye' => $internalYeStock,
            'dropship_ye' => $dropshipYeStock,
            'staging_sa' => $stagingSaStock,
            'quarantine_total' => $quarantineTotal,
            'quarantine_sa' => $quarantineSaStock,
            'quarantine_ye' => $quarantineYeStock,
            'allocated_total' => $allocatedTotal,
            'in_transit_qty' => $inTransitItemsQty,
            'in_transit_count' => $inTransitCount,
            'discrepancies_total' => $discrepanciesTotal,
            'damaged_total' => $totalDamagedReceived,
            'missing_total' => $totalMissingReceived,
            'stalled_total' => $stalledTotal,
            'virtual_projection' => $virtualProjectionStock,
        ];

        // Distribution Breakdown
        $distribution = [
            'مخزون داخلي جاهز (صنعاء)' => $internalYeStock,
            'توزيع دروبشوبنج ترانزيت (صنعاء)' => $dropshipYeStock,
            'تجميع وتوريد خارجي (الرياض)' => $stagingSaStock,
            'تحت النقل الدولي' => $inTransitItemsQty,
            'حجر وتوالف (السعودية واليمن)' => $quarantineTotal,
        ];

        // Recent 10 Movements
        $recentMovements = InventoryMovement::with(['sourceInventorySource', 'targetInventorySource', 'actor'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('inventory::admin.dashboard.index', compact('stats', 'distribution', 'recentMovements', 'sources'));
    }
}
