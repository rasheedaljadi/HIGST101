<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Procurement\DataGrids\ProcurementExceptionDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Security\ProcurementAcl;

class ExceptionController extends Controller
{
    use AuthorizesProcurementActions;

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_EXCEPTION_HANDLE);

        if ($request->ajax()) {
            return datagrid(ProcurementExceptionDataGrid::class)->process();
        }

        return view('procurement::admin.exceptions.index');
    }
}
