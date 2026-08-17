<?php

namespace Webkul\DeliveryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\User\Models\Admin;

class AdminDeliveryController extends Controller
{
    public function __construct(
        protected HandoffExecutionService $handoffExecutionService,
        protected DeliveryLifecycleService $deliveryLifecycleService
    ) {}

    /**
     * List delivery assignments for operations supervisors.
     */
    public function index(Request $request)
    {
        $query = DeliveryAssignment::with(['order', 'deliveryBoy', 'deliveryPoint', 'attemptLogs', 'cashCollections']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('delivery_type')) {
            $query->where('delivery_type', $request->input('delivery_type'));
        }

        if ($request->filled('delivery_boy_id')) {
            $query->where('delivery_boy_id', $request->input('delivery_boy_id'));
        }

        if ($request->filled('delivery_point_id')) {
            $query->where('delivery_point_id', $request->input('delivery_point_id'));
        }

        $assignments = $query->orderBy('id', 'desc')->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $assignments,
            ]);
        }

        $user = Auth::guard('admin')->user();
        $couriers = Admin::where('status', 1)->get();
        $deliveryPoints = DeliveryPoint::where('is_active', true)->get();

        return view('delivery::admin.index', [
            'assignments' => $assignments,
            'user' => $user,
            'couriers' => $couriers,
            'deliveryPoints' => $deliveryPoints,
            'points' => $deliveryPoints,
        ]);
    }

    /**
     * Assign courier or delivery point to assignment.
     */
    public function assign(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        $request->validate([
            'delivery_boy_id' => 'nullable|integer|exists:admins,id',
            'delivery_point_id' => 'nullable|integer|exists:delivery_points,id',
        ]);

        try {
            if ($request->filled('delivery_boy_id')) {
                $assignment = $this->deliveryLifecycleService->assignToCourier(
                    $assignment,
                    (int) $request->input('delivery_boy_id'),
                    $user->id
                );
            }

            if ($request->filled('delivery_point_id')) {
                $assignment = $this->deliveryLifecycleService->assignToDeliveryPoint(
                    $assignment,
                    (int) $request->input('delivery_point_id'),
                    $user->id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إسناد مهمة التوصيل بنجاح.',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute handoff from central inventory (hayest_central).
     */
    public function handoff(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        try {
            $updated = $this->handoffExecutionService->executeHandoff(
                orderId: $assignment->order_id,
                actorId: $user->id,
                actorType: 'supervisor',
                idempotencyKey: $request->input('idempotency_key')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تسليم الشحنة من المستودع المركزي بنجاح.',
                'data' => $updated,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve return to Hayest Central and restore inventory.
     */
    public function returnToHayest(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $updated = $this->deliveryLifecycleService->returnToHayest(
                assignment: $assignment,
                supervisorId: $user->id,
                reason: $request->input('reason'),
                idempotencyKey: $request->input('idempotency_key')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد إرجاع الشحنة وإعادة الكمية للمخزون المركزي بنجاح.',
                'data' => $updated,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
