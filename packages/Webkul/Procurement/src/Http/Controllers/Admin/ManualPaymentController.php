<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Procurement\DataGrids\ProcurementManualPaymentDataGrid;
use Webkul\Procurement\Services\ProcurementManualPaymentService;

class ManualPaymentController extends Controller
{
    public function __construct(
        protected ProcurementManualPaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datagrid(ProcurementManualPaymentDataGrid::class)->process();
        }

        return view('procurement::admin.manual_payments.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_purchase_order_id' => 'required|integer|exists:supplier_purchase_orders,id',
            'external_reference' => 'required|string|min:3',
            'declared_total' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|in:USD',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->paymentService->declarePayment(
                (int) $request->input('supplier_purchase_order_id'),
                (int) Auth::id(),
                $request->input('external_reference'),
                (float) $request->input('declared_total'),
                $request->input('currency', 'USD'),
                null,
                $request->input('notes')
            );

            session()->flash('success', trans('procurement::app.messages.payment-declared-success'));

            return redirect()->back();
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }
}
