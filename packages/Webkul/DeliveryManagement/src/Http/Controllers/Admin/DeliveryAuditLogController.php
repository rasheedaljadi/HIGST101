<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\DeliveryManagement\DataGrids\DeliveryAuditLogDataGrid;

class DeliveryAuditLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryAuditLogDataGrid::class)->process();
        }

        return view('delivery::admin.audit-logs.index');
    }
}
