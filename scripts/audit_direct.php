<?php

use Illuminate\Contracts\Console\Kernel;
use Webkul\Admin\Services\HayestDashboardAggregationService;
use Webkul\Sales\Services\Lifecycle\OrderLifecycleDashboardQueryService;
use Webkul\User\Models\Admin;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Create/actingAs admin user
$admin = Admin::first() ?? Admin::factory()->create();
auth()->guard('admin')->login($admin);

$service = app(OrderLifecycleDashboardQueryService::class);
$summary = $service->getPipelineSummary();
$dq = $service->getUnclassifiedDataQualityInfo();
$currency = core()->currencySymbol(core()->getBaseCurrencyCode());

$adminService = app(HayestDashboardAggregationService::class);
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
    ],
];

file_put_contents(__DIR__.'/audit_output.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'AUDIT_SUCCESS_HTML_LEN:'.strlen($html)."\n";
