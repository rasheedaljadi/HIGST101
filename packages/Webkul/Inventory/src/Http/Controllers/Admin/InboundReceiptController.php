<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Fulfillment\Models\InboundReceiptManifest;
use Webkul\Fulfillment\Models\InventoryTransferManifest;
use Webkul\Fulfillment\Services\InboundReceiptService;
use Webkul\Inventory\DataGrids\InboundReceiptDataGrid;
use Webkul\Inventory\Models\InventorySource;

class InboundReceiptController extends Controller
{
    public function __construct(
        protected InboundReceiptService $inboundReceiptService
    ) {}

    /**
     * Display inbound receipt manifests DataGrid.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InboundReceiptDataGrid::class)->process();
        }

        return view('inventory::admin.receipts.index');
    }

    /**
     * Show form for inspecting and creating an inbound receipt.
     */
    public function create(Request $request)
    {
        $transferManifestId = $request->query('transfer_manifest_id');
        $transferManifest = null;

        if ($transferManifestId) {
            $transferManifest = InventoryTransferManifest::with(['items.product', 'sourceInventorySource', 'destinationInventorySource'])
                ->find($transferManifestId);
        }

        $activeTransfers = InventoryTransferManifest::whereIn('status', ['in_transit', 'partially_received', 'draft'])
            ->orderBy('id', 'desc')
            ->get();

        $destinationSources = InventorySource::whereIn('code', ['hayest_dropship_ye', 'hayest_internal_ye'])->get();
        $quarantineSources = InventorySource::whereIn('code', ['hayest_quarantine_ye', 'hayest_quarantine_sa'])->get();

        return view('inventory::admin.receipts.create', compact(
            'transferManifest',
            'activeTransfers',
            'destinationSources',
            'quarantineSources'
        ));
    }

    /**
     * Get details of a transfer manifest including full item breakdown.
     */
    public function getTransferManifestDetails(int $id)
    {
        $manifest = InventoryTransferManifest::with([
            'items.product',
            'sourceInventorySource',
            'destinationInventorySource',
        ])->find($id);

        if (! $manifest) {
            return response()->json(['error' => 'Transfer manifest not found'], 404);
        }

        $locale = app()->getLocale();

        $items = $manifest->items->map(function ($item) use ($locale) {
            $productFlat = DB::table('product_flat')
                ->where('product_id', $item->product_id)
                ->where('locale', $locale)
                ->first();

            $image = DB::table('product_images')
                ->where('product_id', $item->product_id)
                ->value('path');

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'name' => $productFlat?->name ?? $item->product?->name ?? $item->sku,
                'qty_shipped' => (int) $item->qty_shipped,
                'qty_good' => (int) $item->qty_shipped,
                'qty_damaged' => 0,
                'qty_missing' => 0,
                'image_url' => $image ? asset('storage/'.$image) : null,
            ];
        });

        return response()->json([
            'id' => $manifest->id,
            'manifest_number' => $manifest->manifest_number,
            'status' => $manifest->status?->value ?? $manifest->status,
            'carrier_name' => $manifest->carrier_name,
            'tracking_number' => $manifest->tracking_number,
            'source_inventory_source_id' => $manifest->source_inventory_source_id,
            'destination_inventory_source_id' => $manifest->destination_inventory_source_id,
            'source_name' => $manifest->sourceInventorySource?->name ?? $manifest->source?->name,
            'destination_name' => $manifest->destinationInventorySource?->name ?? $manifest->destination?->name,
            'items' => $items,
        ]);
    }

    /**
     * Preview expected inventory impact before committing receipt.
     */
    public function preview(Request $request)
    {
        $items = $request->input('items', []);

        $totalGood = 0;
        $totalDamaged = 0;
        $totalMissing = 0;

        foreach ($items as $item) {
            $totalGood += max(0, (int) ($item['qty_good'] ?? 0));
            $totalDamaged += max(0, (int) ($item['qty_damaged'] ?? 0));
            $totalMissing += max(0, (int) ($item['qty_missing'] ?? 0));
        }

        return response()->json([
            'total_good' => $totalGood,
            'total_damaged' => $totalDamaged,
            'total_missing' => $totalMissing,
            'will_route_to_salable' => $totalGood > 0,
            'will_route_to_quarantine' => $totalDamaged > 0,
            'has_discrepancies' => ($totalDamaged > 0 || $totalMissing > 0),
        ]);
    }

    /**
     * Process official inbound receipt through InboundReceiptService.
     */
    public function store(Request $request)
    {
        $request->validate([
            'destination_inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'quarantine_inventory_source_id' => 'nullable|integer|exists:inventory_sources,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.sku' => 'required|string',
            'items.*.qty_good' => 'nullable|integer|min:0',
            'items.*.qty_damaged' => 'nullable|integer|min:0',
            'items.*.qty_missing' => 'nullable|integer|min:0',
        ]);

        try {
            $admin = auth()->guard('admin')->user();
            $actorId = $admin ? $admin->id : 1;

            $receipt = $this->inboundReceiptService->processInboundReceipt($request->all(), $actorId);

            session()->flash('success', trans('inventory::app.admin.receipts.success-message')." (رقم الاستلام: #{$receipt->receipt_number})");

            return redirect()->route('admin.inventory.receipts.show', $receipt->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show inbound receipt details.
     */
    public function show(int $id)
    {
        $receipt = InboundReceiptManifest::with(['destinationInventorySource', 'quarantineInventorySource', 'transferManifest', 'items', 'receivedByAdmin'])
            ->findOrFail($id);

        return view('inventory::admin.receipts.view', compact('receipt'));
    }
}
