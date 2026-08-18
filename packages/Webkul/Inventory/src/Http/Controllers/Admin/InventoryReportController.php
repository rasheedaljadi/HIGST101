<?php

namespace Webkul\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Inventory\Services\InventoryReportingService;

class InventoryReportController extends Controller
{
    public function __construct(
        protected InventoryReportingService $reportingService
    ) {}

    /**
     * Display unified reports page.
     */
    public function index(Request $request)
    {
        $type = $request->query('report_type', 'movements');
        $filters = $request->only(['date_from', 'date_to', 'sku', 'movement_type', 'status']);

        $reportData = match ($type) {
            'sources' => $this->reportingService->getSourcesBalanceReport($filters),
            'transfers' => $this->reportingService->getTransfersReport($filters),
            'receipts' => $this->reportingService->getReceiptsDiscrepanciesReport($filters),
            'allocations' => $this->reportingService->getAllocationsReport($filters),
            'reconciliation' => $this->reportingService->getReconciliationReport($filters),
            'unclassified' => $this->reportingService->getUnclassifiedProductsReport($filters),
            default => $this->reportingService->getMovementsReport($filters),
        };

        return view('inventory::admin.reports.index', compact('type', 'reportData', 'filters'));
    }

    /**
     * Export selected report as CSV.
     */
    public function export(string $type, Request $request): StreamedResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'sku', 'movement_type', 'status']);

        $data = match ($type) {
            'sources' => $this->reportingService->getSourcesBalanceReport($filters),
            'transfers' => $this->reportingService->getTransfersReport($filters),
            'receipts' => $this->reportingService->getReceiptsDiscrepanciesReport($filters),
            'allocations' => $this->reportingService->getAllocationsReport($filters),
            'reconciliation' => $this->reportingService->getReconciliationReport($filters),
            'unclassified' => $this->reportingService->getUnclassifiedProductsReport($filters),
            default => $this->reportingService->getMovementsReport($filters),
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"hayest_inventory_report_{$type}_".date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8 Excel support

            if ($data->isNotEmpty()) {
                fputcsv($handle, array_keys((array) $data->first()));

                foreach ($data as $row) {
                    fputcsv($handle, (array) $row);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
