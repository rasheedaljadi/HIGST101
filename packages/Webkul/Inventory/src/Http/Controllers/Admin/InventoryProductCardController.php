<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\DataGrids\InventoryProductCardDataGrid;
use Webkul\Inventory\Models\InventoryMovement;
use Webkul\Product\Models\Product;

class InventoryProductCardController extends Controller
{
    /**
     * Display product inventory cards DataGrid.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InventoryProductCardDataGrid::class)->process();
        }

        return view('inventory::admin.products.index');
    }

    /**
     * Display detailed source breakdown for a single product.
     */
    public function show(int $id)
    {
        $product = Product::findOrFail($id);

        $targetProductIds = [$id];
        if ($product->type === 'configurable') {
            $variantIds = $product->variants()->pluck('id')->toArray();
            $targetProductIds = array_merge($targetProductIds, $variantIds);
        }

        // Fetch balances per source
        $sources = DB::table('inventory_sources')
            ->leftJoin('product_inventories', function ($join) use ($targetProductIds) {
                $join->on('inventory_sources.id', '=', 'product_inventories.inventory_source_id')
                    ->whereIn('product_inventories.product_id', $targetProductIds);
            })
            ->select(
                'inventory_sources.id',
                'inventory_sources.code',
                'inventory_sources.name',
                'inventory_sources.country',
                'inventory_sources.city',
                'inventory_sources.source_type',
                'inventory_sources.is_salable',
                'inventory_sources.is_delivery_source',
                DB::raw('COALESCE(SUM(product_inventories.qty), 0) as current_qty')
            )
            ->groupBy(
                'inventory_sources.id',
                'inventory_sources.code',
                'inventory_sources.name',
                'inventory_sources.country',
                'inventory_sources.city',
                'inventory_sources.source_type',
                'inventory_sources.is_salable',
                'inventory_sources.is_delivery_source'
            )
            ->get();

        $virtualProjection = $sources->firstWhere('code', 'aliexpress_source');
        $legacySources = $sources->where('code', 'default');
        $localSources = $sources->where('code', '!=', 'aliexpress_source');

        $totalSalableLocal = $localSources->where('is_salable', 1)->where('is_delivery_source', 1)->sum('current_qty');

        // Fetch active order allocations for this product
        $allocations = DB::table('order_allocations')
            ->leftJoin('orders', 'order_allocations.order_id', '=', 'orders.id')
            ->where('order_allocations.product_id', $id)
            ->where('order_allocations.state', 'reserved')
            ->select(
                'order_allocations.id',
                'order_allocations.order_id',
                'orders.increment_id as order_increment_id',
                'order_allocations.source_code',
                'order_allocations.reserved_qty',
                'order_allocations.created_at'
            )
            ->get();

        // Fetch movement ledger for this product
        $movements = InventoryMovement::with(['sourceInventorySource', 'targetInventorySource', 'actor'])
            ->where('product_id', $id)
            ->orderBy('id', 'desc')
            ->limit(25)
            ->get();

        return view('inventory::admin.products.view', compact(
            'product',
            'sources',
            'virtualProjection',
            'localSources',
            'totalSalableLocal',
            'allocations',
            'movements'
        ));
    }
}
