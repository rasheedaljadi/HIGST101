<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Procurement\DataGrids\ProcurementExceptionDataGrid;

class ExceptionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(ProcurementExceptionDataGrid::class)->process();
        }

        return view('procurement::admin.exceptions.index');
    }
}
