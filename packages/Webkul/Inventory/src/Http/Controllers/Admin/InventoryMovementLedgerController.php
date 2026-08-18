<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Inventory\DataGrids\InventoryMovementDataGrid;

class InventoryMovementLedgerController extends Controller
{
    /**
     * Display immutable movements ledger DataGrid.
     */
    public function index(Request $request)
    {
        if (request()->ajax()) {
            return datagrid(InventoryMovementDataGrid::class)->process();
        }

        return view('inventory::admin.movements.index');
    }
}
