<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\DeliveryManagement\DataGrids\DeliveryAttemptLogDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAttemptLog;

class DeliveryFailureController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryAttemptLogDataGrid::class)->process();
        }

        $exhaustedAssignments = DeliveryAssignment::with(['order', 'deliveryBoy', 'deliveryPoint'])
            ->where('status', 'delivery_failed')
            ->orderBy('id', 'desc')
            ->get();

        $retryScheduledAssignments = DeliveryAssignment::with(['order', 'deliveryBoy'])
            ->where('status', 'retry_scheduled')
            ->orderBy('id', 'desc')
            ->get();

        $totalAttempts = DeliveryAttemptLog::count();

        return view('delivery::admin.failures.index', compact('exhaustedAssignments', 'retryScheduledAssignments', 'totalAttempts'));
    }
}
