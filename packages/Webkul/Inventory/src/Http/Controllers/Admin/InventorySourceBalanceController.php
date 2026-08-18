<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Inventory\DataGrids\InventorySourceBalanceDataGrid;

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
}
