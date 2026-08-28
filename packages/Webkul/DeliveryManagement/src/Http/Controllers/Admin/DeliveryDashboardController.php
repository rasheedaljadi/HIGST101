<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;

class DeliveryDashboardController extends Controller
{
    /**
     * Handle root /admin/delivery redirection based on user role.
     *
     * @return RedirectResponse
     */
    public function root(Request $request)
    {
        $user = auth()->guard('admin')->user();
        if ($user && in_array($user->role?->name, ['Courier', 'PointAgent'])) {
            return redirect()->route('delivery.index');
        }

        return redirect()->route('admin.delivery.dashboard.index');
    }

    public function index(Request $request)
    {
        $today = now()->startOfDay();

        $stats = [
            'ready_for_assignment' => DeliveryAssignment::where('status', 'ready_for_assignment')->count(),
            'assigned' => DeliveryAssignment::where('status', 'assigned')->count(),
            'picked_up' => DeliveryAssignment::where('status', 'picked_up')->count(),
            'out_for_delivery' => DeliveryAssignment::where('status', 'out_for_delivery')->count(),
            'arrived_at_point' => DeliveryAssignment::where('status', 'arrived_at_point')->count(),
            'delivered_today' => DeliveryAssignment::where('status', 'delivered')->where('updated_at', '>=', $today)->count(),
            'delivered_total' => DeliveryAssignment::where('status', 'delivered')->count(),
            'delivery_failed' => DeliveryAssignment::where('status', 'delivery_failed')->count(),
            'retry_scheduled' => DeliveryAssignment::where('status', 'retry_scheduled')->count(),
            'returned_to_hayest' => DeliveryAssignment::where('status', 'returned_to_hayest')->count(),
            'cod_collected_today' => (float) DB::table('delivery_cash_collections')->where('collected_at', '>=', $today)->sum('amount'),
            'pending_settlements' => DB::table('delivery_settlements')->where('status', 'pending')->count(),
            'total_active' => DeliveryAssignment::whereIn('status', ['assigned', 'picked_up', 'out_for_delivery', 'arrived_at_point'])->count(),
        ];

        // Status distribution for chart
        $statusDistribution = [
            'جاهز للإسناد' => $stats['ready_for_assignment'],
            'مسند' => $stats['assigned'],
            'مع المندوب' => $stats['picked_up'] + $stats['out_for_delivery'],
            'في النقطة' => $stats['arrived_at_point'],
            'تم التسليم' => $stats['delivered_total'],
            'متعثر/إعادة' => $stats['delivery_failed'] + $stats['retry_scheduled'],
            'مرتجع للمركزي' => $stats['returned_to_hayest'],
        ];

        $recentAssignments = DeliveryAssignment::with(['order', 'deliveryBoy', 'deliveryPoint'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return view('delivery::admin.dashboard.index', compact('stats', 'statusDistribution', 'recentAssignments'));
    }
}
