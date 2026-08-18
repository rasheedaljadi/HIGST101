<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Webkul\Fulfillment\Models\InventoryTransferManifest;
use Webkul\Fulfillment\Services\TransferManifestService;
use Webkul\Inventory\DataGrids\InventoryTransferDataGrid;
use Webkul\Inventory\Models\InventorySource;

class InventoryTransferController extends Controller
{
    public function __construct(
        protected TransferManifestService $transferManifestService
    ) {}

    /**
     * Display list of transfer manifests.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InventoryTransferDataGrid::class)->process();
        }

        return view('inventory::admin.transfers.index');
    }

    /**
     * Show form for creating draft transfer manifest.
     */
    public function create()
    {
        // Physical sources only (Exclude virtual catalog projection)
        $sources = InventorySource::where('status', 1)
            ->where('code', '!=', 'aliexpress_source')
            ->where('source_type', '!=', 'virtual_projection')
            ->get();

        return view('inventory::admin.transfers.create', compact('sources'));
    }

    /**
     * Store new transfer manifest.
     */
    public function store(Request $request)
    {
        $request->validate([
            'source_inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'destination_inventory_source_id' => 'required|integer|exists:inventory_sources,id|different:source_inventory_source_id',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.sku' => 'required|string',
            'items.*.qty_shipped' => 'required|integer|min:1',
        ]);

        // Security check: Never allow transfer originating from virtual AliExpress source
        $source = InventorySource::findOrFail($request->source_inventory_source_id);
        if ($source->code === 'aliexpress_source' || $source->source_type?->value === 'virtual_projection') {
            session()->flash('error', trans('inventory::app.admin.transfers.virtual-source-error'));

            return redirect()->back()->withInput();
        }

        try {
            $admin = auth()->guard('admin')->user();
            $actorId = $admin ? $admin->id : 1;

            $manifest = $this->transferManifestService->createManifest($request->all(), $actorId);

            session()->flash('success', "تم إنشاء مانيفست النقل #{$manifest->manifest_number} بنجاح.");

            return redirect()->route('admin.inventory.transfers.show', $manifest->id);
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show transfer manifest details.
     */
    public function show(int $id)
    {
        $manifest = InventoryTransferManifest::with(['sourceInventorySource', 'destinationInventorySource', 'items', 'creator', 'receiver'])
            ->findOrFail($id);

        return view('inventory::admin.transfers.view', compact('manifest'));
    }

    /**
     * Dispatch draft transfer manifest.
     */
    public function dispatchManifest(int $id, Request $request)
    {
        try {
            $admin = auth()->guard('admin')->user();
            $actorId = $admin ? $admin->id : 1;

            $manifest = $this->transferManifestService->dispatchManifest(
                $id,
                $actorId,
                $request->input('tracking_number'),
                $request->input('carrier_name')
            );

            session()->flash('success', "تم اعتماد وإرسال مانيفست النقل #{$manifest->manifest_number} وتحويله إلى قيد النقل.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.inventory.transfers.show', $id);
    }
}
