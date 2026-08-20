<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Procurement\DataGrids\ProcurementDemandDataGrid;
use Webkul\Procurement\Models\ProcurementDemand;

class ProcurementDemandController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(ProcurementDemandDataGrid::class)->process();
        }

        $counts = [
            'open_for_batching' => ProcurementDemand::where('state', 'open_for_batching')->count(),
            'batched' => ProcurementDemand::where('state', 'batched')->count(),
            'fulfilled' => ProcurementDemand::where('state', 'fulfilled')->count(),
            'locally_covered' => ProcurementDemand::where('state', 'locally_covered')->count(),
        ];

        return view('procurement::admin.demands.index', compact('counts'));
    }
}
