<?php

namespace Webkul\DeliveryManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\DeliveryManagement\Models\DeliveryAssignment;
use Webkul\DeliveryManagement\Services\DeliveryLifecycleService;

class DeliveryAgentController extends Controller
{
    public function __construct(
        protected DeliveryLifecycleService $deliveryLifecycleService
    ) {}

    /**
     * Display courier tasks dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('admin')->user();

        if (! $user) {
            return redirect()->route('admin.session.create');
        }

        $query = DeliveryAssignment::with(['order', 'deliveryPoint', 'attemptLogs']);

        if (isset($user->delivery_point_id) && $user->delivery_point_id) {
            $query->forDeliveryPoint($user->delivery_point_id);
        } else {
            $query->forAgent($user->id);
        }

        $status = $request->get('status');
        if ($status) {
            $query->where('status', $status);
        }

        $assignments = $query->orderBy('id', 'desc')->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $assignments,
            ]);
        }

        return view('delivery::delivery.index', compact('assignments', 'user'));
    }

    /**
     * Show task details.
     */
    public function show(int $id, Request $request)
    {
        $user = Auth::guard('admin')->user();

        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::with(['order', 'deliveryPoint', 'attemptLogs', 'cashCollections'])->findOrFail($id);

        $isAdmin = ($user->role?->permission_type === 'all' || $user->role?->name === 'Administrator');

        // Access control
        if (! isset($user->delivery_point_id) || ! $user->delivery_point_id) {
            if ($assignment->delivery_boy_id !== $user->id && ! $isAdmin) {
                abort(403, 'Unauthorized access to assignment.');
            }
        } elseif ($assignment->delivery_point_id !== (int) $user->delivery_point_id && ! $isAdmin) {
            abort(403, 'Unauthorized access to assignment.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $assignment,
            ]);
        }

        return view('delivery::delivery.show', compact('assignment', 'user'));
    }

    /**
     * Start delivery (courier on the way to customer).
     */
    public function startDelivery(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        try {
            $updated = $this->deliveryLifecycleService->startDelivery($assignment, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'تم بدء مسار التوصيل بنجاح.',
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
     * Confirm arrival at pickup point.
     */
    public function confirmArrivalAtPoint(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        try {
            $updated = $this->deliveryLifecycleService->confirmArrivalAtPoint(
                $assignment,
                $user->id,
                $user->delivery_point_id ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد وصول الشحنة لنقطة التوزيع بنجاح.',
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
     * Confirm final delivery and collect COD cash.
     */
    public function confirmCustomerDelivery(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        $request->validate([
            'collected_amount' => 'nullable|numeric|min:0',
            'collected_currency' => 'nullable|string|max:3',
        ]);

        $orderCurrency = $assignment->order?->order_currency_code ?: (core()->getBaseCurrencyCode() ?: 'USD');
        $collectedCurrency = $request->input('collected_currency') ?: $orderCurrency;

        // Rule 7: Reject mismatch in Phase 1
        if ($request->filled('collected_currency') && $request->input('collected_currency') !== $orderCurrency) {
            return response()->json([
                'success' => false,
                'message' => "عملة التحصيل ({$request->input('collected_currency')}) لا تطابق عملة الطلب ({$orderCurrency}).",
            ], 422);
        }

        try {
            $updated = $this->deliveryLifecycleService->confirmCustomerDelivery(
                assignment: $assignment,
                actorId: $user->id,
                actorType: 'courier',
                collectedAmount: $request->input('collected_amount') ? (float) $request->input('collected_amount') : null,
                currency: $collectedCurrency,
                idempotencyKey: $request->input('idempotency_key')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد التسليم النهائي بنجاح.',
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
     * Record delivery failure.
     */
    public function recordFailure(int $id, Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        /** @var DeliveryAssignment $assignment */
        $assignment = DeliveryAssignment::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
            'schedule_retry' => 'nullable|boolean',
        ]);

        try {
            $updated = $this->deliveryLifecycleService->recordDeliveryFailure(
                assignment: $assignment,
                reason: $request->input('reason'),
                actorId: $user->id,
                scheduleRetry: $request->boolean('schedule_retry', true)
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل سبب عدم التسليم بنجاح.',
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
