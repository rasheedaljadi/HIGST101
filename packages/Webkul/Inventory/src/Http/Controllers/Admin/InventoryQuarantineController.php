<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Inventory\DataGrids\InventoryQuarantineDataGrid;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Inventory\Services\InventoryMovementService;

class InventoryQuarantineController extends Controller
{
    public function __construct(
        protected InventoryMovementService $inventoryMovementService
    ) {}

    /**
     * Display quarantine items DataGrid.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InventoryQuarantineDataGrid::class)->process();
        }

        $salableSources = InventorySource::where('is_salable', 1)->where('status', 1)->get();

        return view('inventory::admin.quarantine.index', compact('salableSources'));
    }

    /**
     * Release item from quarantine to a salable destination.
     * Requires Supervisor or Administrator ACL.
     */
    public function release(int $id, Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'target_inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'reason' => 'required|string|min:5',
        ]);

        $record = DB::table('product_inventories')
            ->where('id', $id)
            ->first();

        if (! $record) {
            session()->flash('error', 'سجل الحجر غير موجود.');

            return redirect()->back();
        }

        $product = DB::table('products')->where('id', $record->product_id)->first();
        if (! $product) {
            session()->flash('error', 'المنتج غير موجود.');

            return redirect()->back();
        }

        $admin = auth()->guard('admin')->user();
        $actorId = $admin ? $admin->id : 1;

        try {
            $idempotencyKey = 'QUAR_REL_'.Str::upper(Str::random(12));

            $this->inventoryMovementService->releaseFromQuarantine(
                productId: $record->product_id,
                sku: $product->sku,
                quantity: (int) $request->quantity,
                quarantineSourceId: $record->inventory_source_id,
                targetSalableSourceId: (int) $request->target_inventory_source_id,
                actorId: $actorId,
                idempotencyKey: $idempotencyKey,
                reason: $request->reason
            );

            session()->flash('success', trans('inventory::app.admin.quarantine.release-success'));
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.inventory.quarantine.index');
    }
}
