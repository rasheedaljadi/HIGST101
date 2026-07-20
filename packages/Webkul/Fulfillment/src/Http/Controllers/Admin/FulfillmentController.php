<?php

namespace Webkul\Fulfillment\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Fulfillment\Repositories\PurchaseOrderRepository;
use Webkul\Fulfillment\Repositories\FulfillmentApprovalRequestRepository;
use Webkul\Fulfillment\Repositories\FulfillmentAttemptRepository;
use Webkul\Fulfillment\Repositories\FulfillmentAuditLogRepository;
use Webkul\Fulfillment\Services\FulfillmentProviderRegistry;
use Webkul\Fulfillment\Services\FulfillmentService;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Fulfillment\Models\PurchaseOrder;
use Webkul\Fulfillment\Models\PurchaseOrderItem;

class FulfillmentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PurchaseOrderRepository $purchaseOrderRepository,
        protected FulfillmentApprovalRequestRepository $approvalRequestRepository,
        protected FulfillmentAttemptRepository $attemptRepository,
        protected FulfillmentAuditLogRepository $auditLogRepository,
        protected FulfillmentProviderRegistry $registry,
        protected FulfillmentService $fulfillmentService,
        protected OrderCommentRepository $orderCommentRepository
    ) {}

    public function index()
    {
        if (! config('fulfillment.admin_ui_enabled', true)) {
            abort(403, 'Fulfillment Admin UI is disabled.');
        }

        if (request()->ajax()) {
            if (request()->query('grid') === 'approvals') {
                return datagrid(\Webkul\Fulfillment\DataGrids\FulfillmentApprovalRequestDataGrid::class)->process();
            }
            return datagrid(\Webkul\Fulfillment\DataGrids\PurchaseOrderDataGrid::class)->process();
        }

        // Cache KPIs for 15 minutes (900 seconds)
        $kpis = \Illuminate\Support\Facades\Cache::remember('fulfillment_dashboard_kpis', 900, function () {
            $total = DB::table('purchase_orders')->count();
            
            $success = DB::table('purchase_orders')
                ->whereIn('state', ['submitted', 'shipped', 'delivered'])
                ->count();
            
            $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 100;

            $retries = DB::table('purchase_orders')
                ->where('attempts', '>', 1)
                ->count();
            
            $retryRate = $total > 0 ? round(($retries / $total) * 100, 1) : 0;

            $avgTime = 0;
            $submittedPOs = DB::table('purchase_orders')
                ->join('invoices', 'purchase_orders.order_id', '=', 'invoices.order_id')
                ->whereNotNull('purchase_orders.submitted_at')
                ->select('purchase_orders.submitted_at', 'invoices.created_at')
                ->limit(100)
                ->get();
            
            if ($submittedPOs->isNotEmpty()) {
                $diffs = [];
                foreach ($submittedPOs as $po) {
                    $diffs[] = strtotime($po->submitted_at) - strtotime($po->created_at);
                }
                $avgTime = count($diffs) > 0 ? round(array_sum($diffs) / count($diffs) / 60, 1) : 0; // in minutes
            }

            $lastAttempts = DB::table('fulfillment_attempts')->orderBy('id', 'desc')->limit(50)->get();
            $totalAttempts = $lastAttempts->count();
            $successAttempts = $lastAttempts->where('result', 'success')->count();
            $health = $totalAttempts > 0 ? round(($successAttempts / $totalAttempts) * 100, 1) : 100;

            $waiting = DB::table('purchase_orders')
                ->whereIn('state', ['pending', 'submitting'])
                ->count();

            $needsReview = DB::table('purchase_orders')
                ->where('state', 'needs_manual_review')
                ->count();

            $backlog = 0;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('jobs')) {
                    $backlog = DB::table('jobs')->count();
                }
            } catch (\Throwable $e) {}

            return [
                'successRate' => $successRate,
                'retryRate'   => $retryRate,
                'avgTime'     => $avgTime,
                'health'      => $health,
                'waiting'     => $waiting,
                'needsReview' => $needsReview,
                'backlog'     => $backlog,
            ];
        });

        $alerts = \Illuminate\Support\Facades\Cache::get('fulfillment_active_alerts', []);

        $poCounts = [
            'all' => DB::table('purchase_orders')->count(),
            'awaiting_payment' => DB::table('purchase_orders')->where('state', 'awaiting_payment_to_supplier')->count(),
            'in_progress' => DB::table('purchase_orders')->whereIn('state', ['pending', 'submitting'])->count(),
            'submitted' => DB::table('purchase_orders')->where('state', 'submitted')->count(),
            'completed' => DB::table('purchase_orders')->whereIn('state', ['shipped', 'delivered'])->count(),
        ];

        return view('fulfillment::admin.index', compact('kpis', 'alerts', 'poCounts'));
    }

    /**
     * Render the Financial & Bookkeeping Dashboard index.
     */
    public function financeIndex(Request $request)
    {
        if (! config('fulfillment.admin_ui_enabled', true)) {
            abort(403, 'Fulfillment Admin UI is disabled.');
        }

        // 1. Paginated double-entry ledger entries
        $ledgerEntries = \Webkul\Fulfillment\Models\LedgerEntry::orderBy('id', 'desc')
            ->paginate(15, ['*'], 'ledger_page')
            ->withQueryString();

        // 2. Paginated financial timelines
        $financialTimeline = \Webkul\Fulfillment\Models\FinancialTimeline::orderBy('id', 'desc')
            ->paginate(15, ['*'], 'timeline_page')
            ->withQueryString();

        // 3. Calculate Financial Metrics
        $totalRevenue = DB::table('ledger_entries')->where('account_code', '1010')->sum('debit');
        $totalSupplierCost = DB::table('ledger_entries')->where('account_code', '2010')->sum('credit');
        $cogsPending = DB::table('ledger_entries')->where('account_code', '5010')->sum('debit') - DB::table('ledger_entries')->where('account_code', '5010')->sum('credit');
        $totalProfit = $totalRevenue - $totalSupplierCost;

        return view('fulfillment::admin.finance', [
            'ledgerEntries'     => $ledgerEntries,
            'financialTimeline' => $financialTimeline,
            'totalRevenue'      => $totalRevenue,
            'totalSupplierCost' => $totalSupplierCost,
            'cogsPending'       => $cogsPending,
            'totalProfit'       => $totalProfit,
        ]);
    }

    /**
     * Render the Operations & Resilience Monitoring Dashboard.
     */
    public function monitoringIndex(Request $request)
    {
        if (! config('fulfillment.admin_ui_enabled', true)) {
            abort(403, 'Fulfillment Admin UI is disabled.');
        }

        // 1. Fetch Circuit Breaker Failures
        $failures = (int) \Illuminate\Support\Facades\Cache::get("circuit_breaker:aliexpress:failures", 0);
        $circuitState = $failures >= 5 ? 'OPEN' : 'CLOSED';

        // 2. Fetch Rate Limiting Calls
        $limitKey = "rate_limit:aliexpress:" . date('Y-m-d-H-i');
        $apiCalls = (int) \Illuminate\Support\Facades\Cache::get($limitKey, 0);

        // 3. Fetch Queue Backlog
        $queueBacklog = 0;
        try {
            $queueBacklog = DB::table('jobs')->count();
        } catch (\Exception $e) {
            // Table might not exist
        }

        // 4. Fetch Paginated External API Logs
        $apiLogs = DB::table('external_api_logs')
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'logs_page')
            ->withQueryString();

        return view('fulfillment::admin.monitoring', compact('failures', 'circuitState', 'apiCalls', 'queueBacklog', 'apiLogs'));
    }

    /**
     * Reset the Circuit Breaker manually back to CLOSED state.
     */
    public function resetCircuitBreaker()
    {
        if (! config('fulfillment.admin_ui_enabled', true)) {
            abort(403, 'Fulfillment Admin UI is disabled.');
        }

        \Illuminate\Support\Facades\Cache::forget("circuit_breaker:aliexpress:failures");

        session()->flash('success', 'تم إعادة ضبط وتصفير قاطع الدورة بنجاح. الاتصالات مع AliExpress نشطة الآن.');

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function view(int $id)
    {
        if (! config('fulfillment.admin_ui_enabled', true)) {
            abort(403, 'Fulfillment Admin UI is disabled.');
        }

        // Prevent N+1 queries by eager loading required relationships
        $po = $this->purchaseOrderRepository->with([
            'order',
            'items',
            'fulfillmentAttempts' => fn($q) => $q->orderBy('id', 'desc'),
            'events'              => fn($q) => $q->orderBy('id', 'desc'),
            'auditLogs'           => fn($q) => $q->with('user')->orderBy('id', 'desc'),
            'approvalRequests'    => fn($q) => $q->with(['requestedBy', 'approvedBy'])->orderBy('id', 'desc'),
        ])->findOrFail($id);

        $allocations = \Webkul\Fulfillment\Models\OrderAllocation::with(['logs', 'product', 'variantProduct'])
            ->where('order_id', $po->order_id)
            ->get();

        $procurementSessions = \Webkul\Fulfillment\Models\ProcurementSession::with(['providerAccount'])
            ->whereIn('order_allocation_id', $allocations->pluck('id')->toArray())
            ->orderBy('id', 'desc')
            ->get();

        return view('fulfillment::admin.view', compact('po', 'allocations', 'procurementSessions'));
    }

    /**
     * Retry submitting a failed purchase order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry(int $id)
    {
        if (! config('fulfillment.retry_enabled', true)) {
            session()->flash('error', 'Fulfillment retries are disabled by feature flag.');
            return redirect()->back();
        }

        $po = $this->purchaseOrderRepository->findOrFail($id);

        // State check: Cannot retry if already in final/submitted states
        if (in_array($po->state, [PurchaseOrder::STATE_SUBMITTED, PurchaseOrder::STATE_SHIPPED, PurchaseOrder::STATE_DELIVERED], true)) {
            session()->flash('warning', 'Purchase order is already placed or shipped.');
            return redirect()->back();
        }

        $admin = auth()->guard('admin')->user();

        try {
            // Write audit log
            $this->auditLogRepository->create([
                'purchase_order_id' => $po->id,
                'user_id'           => $admin->id,
                'action'            => 'retry',
                'reason'            => 'Manual Retry triggered by administrator',
                'ip_address'        => request()->ip(),
                'changes_payload'   => ['status' => 'executed'],
            ]);

            // Set PO state back to pending for execution
            $po->update(['state' => PurchaseOrder::STATE_PENDING]);

            // Execute PO submission
            $this->fulfillmentService->executePurchaseOrder($po);

            session()->flash('success', trans('fulfillment::app.admin.actions.action-success'));
        } catch (\Throwable $e) {
            session()->flash('error', trans('fulfillment::app.admin.actions.action-failed', ['error' => $e->getMessage()]));
        }

        return redirect()->back();
    }

    /**
     * Cancel a purchase order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(int $id)
    {
        if (! config('fulfillment.manual_cancel_enabled', true)) {
            session()->flash('error', 'Manual cancellations are disabled.');
            return redirect()->back();
        }

        $this->validate(request(), [
            'reason' => 'required|string|min:10',
        ]);

        $po = $this->purchaseOrderRepository->findOrFail($id);

        if ($po->state === PurchaseOrder::STATE_CANCELED) {
            session()->flash('warning', 'Purchase order is already canceled.');
            return redirect()->back();
        }

        $admin = auth()->guard('admin')->user();
        $reason = request('reason');

        // Check if approval workflow is enabled and PO is paid/active (has external ID or is submitted/shipped)
        $isPaidOrSubmitted = $po->external_order_id || in_array($po->state, [PurchaseOrder::STATE_SUBMITTED, PurchaseOrder::STATE_SHIPPED, PurchaseOrder::STATE_AWAITING_PAYMENT], true);
        
        if (config('fulfillment.approval_workflow.enabled', false) && $isPaidOrSubmitted) {
            // Suspend and create approval request
            $this->approvalRequestRepository->create([
                'purchase_order_id' => $po->id,
                'requested_by'      => $admin->id,
                'action'            => 'cancel',
                'reason'            => $reason,
                'changes_payload'   => [],
                'status'            => 'pending',
            ]);

            $this->auditLogRepository->create([
                'purchase_order_id' => $po->id,
                'user_id'           => $admin->id,
                'action'            => 'cancel',
                'reason'            => $reason,
                'ip_address'        => request()->ip(),
                'changes_payload'   => ['status' => 'pending_approval'],
            ]);

            session()->flash('info', trans('fulfillment::app.admin.actions.approval-submitted'));
            return redirect()->back();
        }

        try {
            DB::beginTransaction();

            $cancelSuccess = true;
            $failReason = null;

            if ($po->external_order_id) {
                $provider = $this->registry->resolve($po->provider);
                
                // 1. Query Current Status
                $status = $provider->getSupplierOrderStatus($po->external_order_id, $po->provider_account_id);
                
                if (in_array(strtolower($status->mappedState), ['shipped', 'delivered', 'completed'], true)) {
                    $cancelSuccess = false;
                    $failReason = 'Cannot cancel; order already shipped or completed by supplier.';
                } else {
                    // 2. Call AliExpress Cancel API
                    $cancelResult = $provider->cancelSupplierOrder($po->external_order_id, $po->provider_account_id);
                    if (!$cancelResult->ok) {
                        $cancelSuccess = false;
                        $failReason = 'AliExpress cancellation rejected: ' . ($cancelResult->message ?? 'Unknown reason');
                    }
                }
            }

            if ($cancelSuccess) {
                $po->update(['state' => PurchaseOrder::STATE_CANCELED]);

                $this->auditLogRepository->create([
                    'purchase_order_id' => $po->id,
                    'user_id'           => $admin->id,
                    'action'            => 'cancel',
                    'reason'            => $reason,
                    'ip_address'        => request()->ip(),
                    'changes_payload'   => ['status' => 'executed'],
                ]);

                $this->orderCommentRepository->create([
                    'order_id'          => $po->order_id,
                    'comment'           => "Purchase Order #{$po->id} canceled. Reason: {$reason}",
                    'customer_notified' => 0,
                ]);

                $this->fulfillmentService->reflectOnCustomerOrder($po->order);

                DB::commit();
                session()->flash('success', trans('fulfillment::app.admin.actions.action-success'));
            } else {
                $po->update([
                    'state'      => PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                    'last_error' => $failReason,
                ]);

                $this->auditLogRepository->create([
                    'purchase_order_id' => $po->id,
                    'user_id'           => $admin->id,
                    'action'            => 'cancel_failed',
                    'reason'            => $failReason,
                    'ip_address'        => request()->ip(),
                    'changes_payload'   => ['status' => 'needs_manual_review'],
                ]);

                $this->orderCommentRepository->create([
                    'order_id'          => $po->order_id,
                    'comment'           => "Cancellation failed for Purchase Order #{$po->id}. Reason: {$failReason}",
                    'customer_notified' => 0,
                ]);

                $this->fulfillmentService->reflectOnCustomerOrder($po->order);

                DB::commit();
                session()->flash('warning', 'AliExpress rejected order cancellation. State moved to Needs Manual Review.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('aliexpress')->error('PO manual cancel failed: ' . $e->getMessage(), ['po' => $id]);
            session()->flash('error', trans('fulfillment::app.admin.actions.action-failed', ['error' => $e->getMessage()]));
        }

        return redirect()->back();
    }

    /**
     * Override state of a purchase order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function overrideState(int $id)
    {
        $this->validate(request(), [
            'state'  => 'required|string',
            'reason' => 'required|string|min:10',
        ]);

        $po = $this->purchaseOrderRepository->findOrFail($id);
        $admin = auth()->guard('admin')->user();
        $state = request('state');
        $reason = request('reason');

        if ($po->state === $state) {
            return redirect()->back();
        }

        if (config('fulfillment.approval_workflow.enabled', false)) {
            // Suspend and create approval request
            $this->approvalRequestRepository->create([
                'purchase_order_id' => $po->id,
                'requested_by'      => $admin->id,
                'action'            => 'state_override',
                'reason'            => $reason,
                'changes_payload'   => ['state' => $state],
                'status'            => 'pending',
            ]);

            $this->auditLogRepository->create([
                'purchase_order_id' => $po->id,
                'user_id'           => $admin->id,
                'action'            => 'state_override',
                'reason'            => $reason,
                'ip_address'        => request()->ip(),
                'changes_payload'   => ['state' => $state, 'status' => 'pending_approval'],
            ]);

            session()->flash('info', trans('fulfillment::app.admin.actions.approval-submitted'));
            return redirect()->back();
        }

        $oldState = $po->state;
        $po->update(['state' => $state]);

        $this->auditLogRepository->create([
            'purchase_order_id' => $po->id,
            'user_id'           => $admin->id,
            'action'            => 'state_override',
            'reason'            => $reason,
            'ip_address'        => request()->ip(),
            'changes_payload'   => ['old_state' => $oldState, 'new_state' => $state, 'status' => 'executed'],
        ]);

        $this->orderCommentRepository->create([
            'order_id'          => $po->order_id,
            'comment'           => "Purchase Order #{$po->id} state overridden to '{$state}'. Reason: {$reason}",
            'customer_notified' => 0,
        ]);

        $this->fulfillmentService->reflectOnCustomerOrder($po->order);

        session()->flash('success', trans('fulfillment::app.admin.actions.action-success'));
        return redirect()->back();
    }

    /**
     * Edit item quantities of a purchase order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editPo(int $id)
    {
        $this->validate(request(), [
            'qty'    => 'required|array',
            'qty.*'  => 'required|integer|min:1',
            'reason' => 'required|string|min:10',
        ]);

        $po = $this->purchaseOrderRepository->findOrFail($id);
        $admin = auth()->guard('admin')->user();
        $qtyData = request('qty');
        $reason = request('reason');

        $isSubmitted = in_array($po->state, [PurchaseOrder::STATE_SUBMITTED, PurchaseOrder::STATE_SHIPPED, PurchaseOrder::STATE_DELIVERED], true);

        if (config('fulfillment.approval_workflow.enabled', false) && $isSubmitted) {
            $this->approvalRequestRepository->create([
                'purchase_order_id' => $po->id,
                'requested_by'      => $admin->id,
                'action'            => 'edit',
                'reason'            => $reason,
                'changes_payload'   => ['qty' => $qtyData],
                'status'            => 'pending',
            ]);

            $this->auditLogRepository->create([
                'purchase_order_id' => $po->id,
                'user_id'           => $admin->id,
                'action'            => 'edit',
                'reason'            => $reason,
                'ip_address'        => request()->ip(),
                'changes_payload'   => ['qty' => $qtyData, 'status' => 'pending_approval'],
            ]);

            session()->flash('info', trans('fulfillment::app.admin.actions.approval-submitted'));
            return redirect()->back();
        }

        // Apply direct edit
        foreach ($qtyData as $itemId => $qty) {
            $item = PurchaseOrderItem::where('purchase_order_id', $po->id)->where('id', $itemId)->first();
            if ($item) {
                $item->update(['qty' => $qty]);
            }
        }

        $this->auditLogRepository->create([
            'purchase_order_id' => $po->id,
            'user_id'           => $admin->id,
            'action'            => 'edit',
            'reason'            => $reason,
            'ip_address'        => request()->ip(),
            'changes_payload'   => ['qty' => $qtyData, 'status' => 'executed'],
        ]);

        session()->flash('success', trans('fulfillment::app.admin.actions.action-success'));
        return redirect()->back();
    }

    /**
     * Refresh state of a purchase order from supplier API.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshStatus(int $id)
    {
        $po = $this->purchaseOrderRepository->findOrFail($id);

        if (!$po->external_order_id) {
            session()->flash('error', 'Cannot sync status: Purchase order does not have an external order ID.');
            return redirect()->back();
        }

        try {
            $provider = $this->registry->resolve($po->provider);
            $status = $provider->getSupplierOrderStatus($po->external_order_id, $po->provider_account_id);

            // Log response
            \Webkul\Fulfillment\Models\FulfillmentProviderEvent::create([
                'purchase_order_id' => $po->id,
                'provider'          => $po->provider,
                'external_state'    => $status->rawState ?? 'unknown',
                'source_type'       => 'manual_refresh',
                'payload'           => [
                    'mapped_state'     => $status->mappedState,
                    'raw_state'        => $status->rawState,
                    'tracking_number'  => $status->trackingNumber,
                    'tracking_company' => $status->trackingCompany,
                ],
                'received_at'       => now(),
                'processed_at'      => now(),
            ]);

            if ($status->mappedState === 'failed_transient') {
                session()->flash('warning', 'Sync status returned transient failure. Please try again later.');
                return redirect()->back();
            }

            if ($status->mappedState === PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW) {
                $po->update([
                    'state'              => PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                    'supplier_state_raw' => $status->rawState,
                ]);

                $this->orderCommentRepository->create([
                    'order_id'          => $po->order_id,
                    'comment'           => trans('fulfillment::app.status_check_failed', ['po' => $po->id, 'external' => $po->external_order_id]),
                    'customer_notified' => 0,
                ]);

                session()->flash('warning', 'PO changed state to Needs Manual Review.');
                return redirect()->back();
            }

            $oldState = $po->state;

            $po->update([
                'state'              => $status->mappedState,
                'supplier_state_raw' => $status->rawState,
                'tracking_number'    => $status->trackingNumber ?? $po->tracking_number,
                'tracking_company'   => $status->trackingCompany ?? $po->tracking_company,
            ]);

            if ($oldState !== $status->mappedState) {
                $this->orderCommentRepository->create([
                    'order_id'          => $po->order_id,
                    'comment'           => trans('fulfillment::app.state_updated', ['po' => $po->id, 'old' => $oldState, 'new' => $status->mappedState, 'raw' => $status->rawState]),
                    'customer_notified' => 0,
                ]);

                $this->fulfillmentService->reflectOnCustomerOrder($po->order);
            }

            session()->flash('success', 'Status refreshed successfully.');
        } catch (\Throwable $e) {
            Log::channel('aliexpress')->error("Error manual polling status for PO #{$po->id}", [
                'po_id'   => $po->id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('error', 'Error syncing status: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Approve a pending approval request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveRequest(int $id)
    {
        $request = $this->approvalRequestRepository->findOrFail($id);

        if ($request->status !== 'pending') {
            session()->flash('warning', 'Approval request is not pending.');
            return redirect()->back();
        }

        $admin = auth()->guard('admin')->user();
        $po = $this->purchaseOrderRepository->findOrFail($request->purchase_order_id);

        try {
            $request->update([
                'status'          => 'approved',
                'approved_by'     => $admin->id,
                'decision_reason' => 'Approved by supervisor',
            ]);

            // Execute the action
            if ($request->action === 'cancel') {
                $cancelSuccess = true;
                $failReason = null;

                if ($po->external_order_id) {
                    $provider = $this->registry->resolve($po->provider);
                    
                    // 1. Query Current Status
                    $status = $provider->getSupplierOrderStatus($po->external_order_id, $po->provider_account_id);
                    
                    if (in_array(strtolower($status->mappedState), ['shipped', 'delivered', 'completed'], true)) {
                        $cancelSuccess = false;
                        $failReason = 'Cannot cancel; order already shipped or completed by supplier.';
                    } else {
                        // 2. Call AliExpress Cancel API
                        $cancelResult = $provider->cancelSupplierOrder($po->external_order_id, $po->provider_account_id);
                        if (!$cancelResult->ok) {
                            $cancelSuccess = false;
                            $failReason = 'AliExpress cancellation rejected: ' . ($cancelResult->message ?? 'Unknown reason');
                        }
                    }
                }

                if ($cancelSuccess) {
                    $po->update(['state' => PurchaseOrder::STATE_CANCELED]);
                    $this->orderCommentRepository->create([
                        'order_id'          => $po->order_id,
                        'comment'           => "Purchase Order #{$po->id} canceled (Approved by supervisor).",
                        'customer_notified' => 0,
                    ]);
                } else {
                    $po->update([
                        'state'      => PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW,
                        'last_error' => $failReason,
                    ]);
                    $this->orderCommentRepository->create([
                        'order_id'          => $po->order_id,
                        'comment'           => "Cancellation failed for Purchase Order #{$po->id} (Approved by supervisor). Reason: {$failReason}",
                        'customer_notified' => 0,
                    ]);
                }
            } elseif ($request->action === 'state_override') {
                $oldState = $po->state;
                $newState = $request->changes_payload['state'];
                $po->update(['state' => $newState]);
                $this->orderCommentRepository->create([
                    'order_id'          => $po->order_id,
                    'comment'           => "Purchase Order #{$po->id} state overridden to '{$newState}' (Approved by supervisor).",
                    'customer_notified' => 0,
                ]);
            } elseif ($request->action === 'edit') {
                $qtyData = $request->changes_payload['qty'];
                foreach ($qtyData as $itemId => $qty) {
                    $item = PurchaseOrderItem::where('purchase_order_id', $po->id)->where('id', $itemId)->first();
                    if ($item) {
                        $item->update(['qty' => $qty]);
                    }
                }
            }

            $this->auditLogRepository->create([
                'purchase_order_id' => $po->id,
                'user_id'           => $admin->id,
                'action'            => $request->action,
                'reason'            => "Approved request #{$request->id}: {$request->reason}",
                'ip_address'        => request()->ip(),
                'changes_payload'   => ['status' => 'approved', 'approval_request_id' => $request->id],
            ]);

            $this->fulfillmentService->reflectOnCustomerOrder($po->order);
            $request->update(['status' => 'executed']);

            session()->flash('success', trans('fulfillment::app.admin.actions.approval-approved'));
        } catch (\Throwable $e) {
            session()->flash('error', 'Approval failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Reject a pending approval request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectRequest(int $id)
    {
        $request = $this->approvalRequestRepository->findOrFail($id);

        if ($request->status !== 'pending') {
            session()->flash('warning', 'Approval request is not pending.');
            return redirect()->back();
        }

        $admin = auth()->guard('admin')->user();

        $request->update([
            'status'          => 'rejected',
            'approved_by'     => $admin->id,
            'decision_reason' => 'Rejected by supervisor',
        ]);

        $this->auditLogRepository->create([
            'purchase_order_id' => $request->purchase_order_id,
            'user_id'           => $admin->id,
            'action'            => $request->action,
            'reason'            => "Rejected request #{$request->id}: {$request->reason}",
            'ip_address'        => request()->ip(),
            'changes_payload'   => ['status' => 'rejected', 'approval_request_id' => $request->id],
        ]);

        session()->flash('success', trans('fulfillment::app.admin.actions.approval-rejected'));
        return redirect()->back();
    }

    /**
     * Dismiss a persistent alert from the dashboard.
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dismissAlert(string $id)
    {
        \Webkul\Fulfillment\Services\FulfillmentAlertService::clearAlert($id);
        
        session()->flash('success', 'Alert dismissed successfully.');
        return redirect()->back();
    }
}
