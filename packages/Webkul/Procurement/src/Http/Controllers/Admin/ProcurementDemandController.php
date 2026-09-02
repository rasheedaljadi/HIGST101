<?php

namespace Webkul\Procurement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AliExpressProductImport;
use App\Services\AliExpress\AliExpressProductSyncer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Procurement\DataGrids\ProcurementDemandDataGrid;
use Webkul\Procurement\Http\Controllers\Admin\Concerns\AuthorizesProcurementActions;
use Webkul\Procurement\Models\ProcurementDemand;
use Webkul\Procurement\Security\ProcurementAcl;

class ProcurementDemandController extends Controller
{
    use AuthorizesProcurementActions;

    public function index(Request $request)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        if ($request->ajax()) {
            return datagrid(ProcurementDemandDataGrid::class)->process();
        }

        $counts = [
            'all' => ProcurementDemand::count(),
            'open_for_batching' => ProcurementDemand::where('state', 'open_for_batching')->count(),
            'batched' => ProcurementDemand::where('state', 'batched')->count(),
            'fulfilled' => ProcurementDemand::where('state', 'fulfilled')->count(),
            'locally_covered' => ProcurementDemand::where('state', 'locally_covered')->count(),
        ];

        return view('procurement::admin.demands.index', compact('counts'));
    }

    public function syncStock(Request $request, AliExpressProductSyncer $syncer)
    {
        $this->authorizeProcurementAction(ProcurementAcl::PERMISSION_VIEW);

        try {
            $demands = ProcurementDemand::where('state', ProcurementDemand::STATE_OPEN_FOR_BATCHING)->get();
            if ($demands->isEmpty()) {
                $demands = ProcurementDemand::whereIn('state', [
                    ProcurementDemand::STATE_OPEN_FOR_BATCHING,
                    ProcurementDemand::STATE_BATCHED,
                ])->latest('id')->limit(10)->get();
            }

            if ($demands->isEmpty()) {
                $msg = 'لا توجد طلبات توريد حالية تتطلب مزامنة المخزون.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                session()->flash('info', $msg);

                return redirect()->back();
            }

            $importIds = $demands->map(fn ($d) => $d->source_snapshot['import_id'] ?? null)->filter()->unique();
            $supplierProductIds = $demands->pluck('supplier_product_id')->filter()->unique();
            $productIds = $demands->pluck('product_id')->filter()->unique();

            $imports = AliExpressProductImport::whereIn('id', $importIds)
                ->orWhereIn('aliexpress_product_id', $supplierProductIds)
                ->orWhereIn('product_id', $productIds)
                ->get()
                ->unique('aliexpress_product_id');

            if ($imports->isEmpty()) {
                $msg = 'لم يتم العثور على سجلات استيراد علي إكسبرس مطابقة للطلبات الحالية.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg]);
                }
                session()->flash('warning', $msg);

                return redirect()->back();
            }

            $syncedCount = 0;
            $failedCount = 0;

            foreach ($imports as $import) {
                try {
                    $syncer->sync($import);
                    $syncedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    Log::channel('aliexpress')->warning("Stock sync failed for import #{$import->id}: ".$e->getMessage());
                }
            }

            $successMsg = "تمت مزامنة وتحديث المخزون الفعلي من علي إكسبرس بنجاح لـ ({$syncedCount}) منتج/صنف.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'synced_count' => $syncedCount,
                ]);
            }

            session()->flash('success', $successMsg);

            return redirect()->route('admin.procurement.demands.index');
        } catch (\Throwable $e) {
            $errMsg = 'حدث خطأ أثناء مزامنة المخزون: '.$e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errMsg], 500);
            }
            session()->flash('error', $errMsg);

            return redirect()->back();
        }
    }
}
