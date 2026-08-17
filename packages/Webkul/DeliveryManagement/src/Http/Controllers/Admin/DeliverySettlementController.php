<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\DataGrids\DeliveryCashCollectionDataGrid;
use Webkul\DeliveryManagement\DataGrids\DeliverySettlementDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\DeliveryManagement\Models\DeliveryCashCollection;
use Webkul\DeliveryManagement\Models\DeliverySettlement;
use Webkul\User\Models\Admin;

class DeliverySettlementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->query('grid') === 'collections') {
                return datagrid(DeliveryCashCollectionDataGrid::class)->process();
            }

            return datagrid(DeliverySettlementDataGrid::class)->process();
        }

        $couriers = Admin::where('status', 1)->get();

        $totalCollected = (float) DB::table('delivery_cash_collections')->sum('amount');
        $totalSettled = (float) DB::table('delivery_settlements')->where('status', 'settled')->sum('actual_amount');
        $totalDifference = (float) DB::table('delivery_settlements')->sum('difference');

        $metrics = [
            'total_collected_yer' => $totalCollected,
            'total_settled_yer' => $totalSettled,
            'unsettled_float_yer' => max(0, $totalCollected - $totalSettled),
            'total_discrepancy' => $totalDifference,
        ];

        return view('delivery::admin.settlements.index', compact('couriers', 'metrics'));
    }

    public function process(Request $request): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        $request->validate([
            'delivery_boy_id' => 'required|integer|exists:admins,id',
            'total_submitted_yer' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $courierId = (int) $request->input('delivery_boy_id');
        $submitted = (float) $request->input('total_submitted_yer');

        $pendingCollections = DeliveryCashCollection::where('delivery_boy_id', $courierId)->get();

        $totalCollected = $pendingCollections->sum('amount');
        $difference = $totalCollected - $submitted;

        try {
            DB::beginTransaction();

            $settlement = DeliverySettlement::create([
                'delivery_boy_id' => $courierId,
                'settlement_date' => now()->toDateString(),
                'expected_amount' => $totalCollected,
                'actual_amount' => $submitted,
                'difference' => $difference,
                'currency' => 'YER',
                'status' => $difference == 0 ? 'settled' : 'discrepancy',
                'settled_by' => $user->id,
                'settled_at' => now(),
                'notes' => $request->input('notes'),
            ]);

            DeliveryAuditLog::log(
                action: 'settlement_processed',
                entityType: 'settlement',
                entityId: $settlement->id,
                reason: 'تنفيذ وتسوية العهدة النقدية للمندوب',
                newValues: [
                    'settlement_id' => $settlement->id,
                    'submitted' => $submitted,
                    'expected' => $totalCollected,
                    'difference' => $difference,
                ],
                userId: $user->id,
                userName: $user->name,
                settlementId: $settlement->id
            );

            DB::commit();

            session()->flash('success', 'تم تنفيذ واعتماد التسوية المالية بنجاح.');
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', 'فشلت التسوية: '.$e->getMessage());
        }

        return redirect()->route('admin.delivery.settlements.index');
    }
}
