<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Get products with positive stock or matching search in the specified source warehouse.
     */
    public function getSourceProducts(int $sourceId, Request $request)
    {
        $query = trim($request->query('query', ''));
        $locale = app()->getLocale();

        $builder = DB::table('product_inventories')
            ->join('products', 'product_inventories.product_id', '=', 'products.id')
            ->leftJoin('product_flat', function ($join) use ($locale) {
                $join->on('products.id', '=', 'product_flat.product_id')
                    ->where('product_flat.locale', '=', $locale);
            })
            ->where('product_inventories.inventory_source_id', $sourceId)
            ->where('product_inventories.qty', '>', 0);

        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('products.sku', 'like', "%{$query}%")
                    ->orWhere('product_flat.name', 'like', "%{$query}%");
            });
        }

        $items = $builder->select(
            'products.id as product_id',
            'products.sku',
            DB::raw('COALESCE(product_flat.name, products.sku) as name'),
            'product_inventories.qty as available_qty',
            DB::raw('(SELECT path FROM product_images WHERE product_images.product_id = products.id ORDER BY id ASC LIMIT 1) as image_path')
        )
            ->orderByDesc('product_inventories.qty')
            ->limit(500)
            ->get()
            ->map(function ($row) {
                return [
                    'product_id' => $row->product_id,
                    'sku' => $row->sku,
                    'name' => $row->name,
                    'available_qty' => (int) $row->available_qty,
                    'image_url' => $row->image_path ? asset('storage/'.$row->image_path) : null,
                ];
            });

        return response()->json($items);
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

        // Validate available stock in source warehouse
        foreach ($request->items as $item) {
            $available = DB::table('product_inventories')
                ->where('inventory_source_id', $request->source_inventory_source_id)
                ->where('product_id', $item['product_id'])
                ->value('qty') ?? 0;

            if ($item['qty_shipped'] > $available) {
                session()->flash('error', "الكمية المطلوبة للصنف {$item['sku']} ({$item['qty_shipped']}) تتجاوز الرصيد المتوفر في المستودع المختار ({$available}).");

                return redirect()->back()->withInput();
            }
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

    /**
     * Cancel draft transfer manifest.
     */
    public function cancelManifest(int $id, Request $request)
    {
        try {
            $admin = auth()->guard('admin')->user();
            $actorId = $admin ? $admin->id : 1;

            $manifest = $this->transferManifestService->cancelManifest(
                $id,
                $actorId,
                $request->input('reason')
            );

            session()->flash('success', "تم إلغاء مانيفست النقل #{$manifest->manifest_number} بنجاح.");
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.inventory.transfers.show', $id);
    }
}
