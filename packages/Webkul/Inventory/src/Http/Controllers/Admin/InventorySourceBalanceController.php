<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\DataGrids\InventorySourceBalanceDataGrid;
use Webkul\Inventory\DataGrids\InventorySourceItemsDataGrid;

class InventorySourceBalanceController extends Controller
{
    /**
     * Display 6 canonical inventory sources and live stock levels.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InventorySourceBalanceDataGrid::class)->process();
        }

        return view('inventory::admin.sources.index');
    }

    /**
     * Display inventory items and details for a specific inventory source.
     */
    public function show(int $id)
    {
        $source = DB::table('inventory_sources')->where('id', $id)->first();

        if (! $source) {
            abort(404);
        }

        if (request()->ajax()) {
            return datagrid(InventorySourceItemsDataGrid::class)->process();
        }

        $currentLocale = app()->getLocale();

        // Calculate summary statistics for this source
        $stats = DB::table('product_inventories as pi')
            ->join('products as p', 'pi.product_id', '=', 'p.id')
            ->leftJoin('product_flat as pf', function ($join) use ($currentLocale) {
                $join->on('p.id', '=', 'pf.product_id')
                    ->where('pf.locale', '=', $currentLocale);
            })
            ->leftJoin('products as parent_p', 'p.parent_id', '=', 'parent_p.id')
            ->leftJoin('product_flat as parent_pf', function ($join) use ($currentLocale) {
                $join->on('parent_p.id', '=', 'parent_pf.product_id')
                    ->where('parent_pf.locale', '=', $currentLocale);
            })
            ->where('pi.inventory_source_id', $id)
            ->selectRaw('
                COUNT(pi.id) as total_items,
                COALESCE(SUM(pi.qty), 0) as total_units,
                COALESCE(SUM(CASE WHEN pi.qty > 0 THEN 1 ELSE 0 END), 0) as in_stock_items,
                COALESCE(SUM(CASE WHEN pi.qty = 0 THEN 1 ELSE 0 END), 0) as out_of_stock_items,
                COALESCE(SUM(pi.qty * COALESCE(pf.price, parent_pf.price, 0)), 0) as total_value
            ')
            ->first();

        return view('inventory::admin.sources.view', compact('source', 'stats'));
    }
}
