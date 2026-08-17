<?php

namespace Webkul\DeliveryManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\DeliveryManagement\DataGrids\DeliveryAssignmentDataGrid;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Models\DeliveryAuditLog;
use Webkul\DeliveryManagement\Models\DeliveryPoint;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;
use Webkul\DeliveryManagement\Services\HandoffExecutionService;
use Webkul\User\Models\Admin;

class DeliveryAssignmentController extends Controller
{
    public function __construct(
        protected HandoffExecutionService $handoffExecutionService,
        protected DeliveryLifecycleService $deliveryLifecycleService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(DeliveryAssignmentDataGrid::class)->process();
        }

        $couriers = Admin::where('status', 1)->get();
        $deliveryPoints = DeliveryPoint::where('is_active', true)->get();

        $statusCounts = [
            'all' => DeliveryAssignment::count(),
            'ready_for_assignment' => DeliveryAssignment::where('status', 'ready_for_assignment')->count(),
            'assigned' => DeliveryAssignment::where('status', 'assigned')->count(),
            'picked_up' => DeliveryAssignment::where('status', 'picked_up')->count(),
            'out_for_delivery' => DeliveryAssignment::where('status', 'out_for_delivery')->count(),
            'arrived_at_point' => DeliveryAssignment::where('status', 'arrived_at_point')->count(),
            'delivered' => DeliveryAssignment::where('status', 'delivered')->count(),
            'delivery_failed' => DeliveryAssignment::where('status', 'delivery_failed')->count(),
            'retry_scheduled' => DeliveryAssignment::where('status', 'retry_scheduled')->count(),
            'returned_to_hayest' => DeliveryAssignment::where('status', 'returned_to_hayest')->count(),
        ];

        return view('delivery::admin.assignments.index', compact('couriers', 'deliveryPoints', 'statusCounts'));
    }

    public function show(int $id)
    {
        $assignment = DeliveryAssignment::with([
            'order.items',
            'order.addresses',
            'order.payment',
            'deliveryBoy',
            'deliveryPoint',
            'attemptLogs.deliveryBoy',
            'cashCollections',
        ])->findOrFail($id);

        $couriers = Admin::where('status', 1)->get();
        $deliveryPoints = DeliveryPoint::where('is_active', true)->get();

        // Fetch audit logs for this assignment
        $auditLogs = DeliveryAuditLog::where('delivery_assignment_id', $id)
            ->orderBy('id', 'desc')
            ->get();

        return view('delivery::admin.assignments.view', compact('assignment', 'couriers', 'deliveryPoints', 'auditLogs'));
    }

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
            $oldValues = [
                'delivery_boy_id' => $assignment->delivery_boy_id,
                'delivery_point_id' => $assignment->delivery_point_id,
                'status' => $assignment->status,
            ];

            if ($request->filled('delivery_boy_id')) {
                $assignment = $this->deliveryLifecycleService->assignToCourier(
                    $assignment,
                    (int) $request->input('delivery_boy_id'),
                    $user->id
                );
            }

            if ($request->filled('delivery_point_id')) {
                $point = DeliveryPoint::findOrFail((int) $request->input('delivery_point_id'));
                if (! $point->is_active) {
                    throw new Exception('لا يمكن الإسناد إلى نقطة تسليم غير نشطة.');
                }

                $assignment = $this->deliveryLifecycleService->assignToDeliveryPoint(
                    $assignment,
                    $point->id,
                    $user->id
                );
            }

            DeliveryAuditLog::log(
                action: 'assigned',
                entityType: 'assignment',
                entityId: $assignment->id,
                reason: 'إسناد المهمة من لوحة الإدارة',
                oldValues: $oldValues,
                newValues: [
                    'delivery_boy_id' => $assignment->delivery_boy_id,
                    'delivery_point_id' => $assignment->delivery_point_id,
                    'status' => $assignment->status,
                ],
                userId: $user->id,
                userName: $user->name,
                assignmentId: $assignment->id
            );

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

    public function handoff(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        try {
            $oldStatus = $assignment->status;

            $updated = $this->handoffExecutionService->executeHandoff(
                orderId: $assignment->order_id,
                actorId: $user->id,
                actorType: 'supervisor',
                idempotencyKey: $request->input('idempotency_key')
            );

            DeliveryAuditLog::log(
                action: 'handoff_completed',
                entityType: 'assignment',
                entityId: $assignment->id,
                reason: 'تسليم وصرف الشحنة من المستودع المركزي للمندوب',
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'picked_up'],
                userId: $user->id,
                userName: $user->name,
                assignmentId: $assignment->id
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

    public function returnToHayest(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $oldStatus = $assignment->status;

            $updated = $this->deliveryLifecycleService->returnToHayest(
                assignment: $assignment,
                supervisorId: $user->id,
                reason: $request->input('reason'),
                idempotencyKey: $request->input('idempotency_key')
            );

            DeliveryAuditLog::log(
                action: 'return_approved',
                entityType: 'assignment',
                entityId: $assignment->id,
                reason: $request->input('reason'),
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'returned_to_hayest'],
                userId: $user->id,
                userName: $user->name,
                assignmentId: $assignment->id
            );

            return response()->json([
                'success' => true,
                'message' => 'تم اعتماد إرجاع الشحنة واستعادة المخزون للمستودع المركزي بنجاح.',
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
