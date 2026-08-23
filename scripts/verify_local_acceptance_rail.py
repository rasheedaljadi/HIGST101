import json
import subprocess
import os

tinker_code = """
$service = app(\\Webkul\\Sales\\Services\\Lifecycle\\OrderLifecycleDashboardQueryService::class);
$summary = $service->getPipelineSummary();
$dq = $service->getUnclassifiedDataQualityInfo();
$currency = core()->currencySymbol(core()->getBaseCurrencyCode());

$adminService = app(\\Webkul\\Admin\\Services\\HayestDashboardAggregationService::class);
$advData = $adminService->getAdvancedData();

$html = view('admin::dashboard.advanced.index', ['advancedData' => $advData])->render();

$result = [
    'stage_count' => count($summary['stages']),
    'stages' => array_column($summary['stages'], 'code'),
    'last_computed_at' => $summary['last_computed_at'],
    'data_quality' => $dq,
    'currency_symbol' => $currency,
    'html_length' => strlen($html),
    'has_section_title' => str_contains($html, 'ORDER LIFECYCLE PIPELINE'),
    'has_arabic_title' => str_contains($html, 'المسار التشغيلي الموحد لدورة حياة الطلبات'),
    'has_unclassified_card' => str_contains($html, 'Unclassified Data Quality Items'),
    'sample_json' => [
        'stages_count' => count($summary['stages']),
        'first_stage' => $summary['stages'][0] ?? null,
        'last_stage' => $summary['stages'][10] ?? null,
        'total_active_orders' => $summary['total_active_orders'],
        'unclassified_count' => $dq['unclassified_count'],
    ]
];

file_put_contents('scripts/audit_output.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'AUDIT_FILE_WRITTEN_SUCCESS';
"""

cmd = f"php artisan tinker --execute=\"{tinker_code}\""
res = subprocess.run(cmd, shell=True, capture_output=True, text=True, cwd=os.getcwd())

print("Stdout:", res.stdout)
print("Stderr:", res.stderr)
