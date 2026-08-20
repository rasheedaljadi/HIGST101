<?php

namespace Webkul\Sales\Services\Lifecycle;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderLifecycleDashboardQueryService
{
    /**
     * Stage Definitions with exact canonical order, labels, icons, groups, and color tokens.
     */
    public const STAGES = [
        'new' => [
            'code' => 'new',
            'rank' => 1,
            'label' => 'طلب جديد',
            'group' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'shopping-bag',
            'color' => '#253691',
            'bg_light' => 'bg-blue-50/90 text-blue-900 border-blue-200',
        ],
        'payment_pending' => [
            'code' => 'payment_pending',
            'rank' => 2,
            'label' => 'بانتظار الدفع',
            'group' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'wallet',
            'color' => '#253691',
            'bg_light' => 'bg-blue-50/90 text-blue-900 border-blue-200',
        ],
        'confirmed' => [
            'code' => 'confirmed',
            'rank' => 3,
            'label' => 'تم التأكيد',
            'group' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'badge-check',
            'color' => '#253691',
            'bg_light' => 'bg-indigo-50/90 text-indigo-900 border-indigo-200',
        ],
        'sourcing_required' => [
            'code' => 'sourcing_required',
            'rank' => 4,
            'label' => 'يحتاج توريداً',
            'group' => 'sourcing',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'receipt-text',
            'color' => '#AB7200',
            'bg_light' => 'bg-amber-50/90 text-amber-900 border-amber-200',
        ],
        'po_created' => [
            'code' => 'po_created',
            'rank' => 5,
            'label' => 'أمر شراء منشأ',
            'group' => 'sourcing',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'file-check',
            'color' => '#AB7200',
            'bg_light' => 'bg-amber-100/80 text-amber-950 border-amber-300',
        ],
        'supplier_shipped' => [
            'code' => 'supplier_shipped',
            'rank' => 6,
            'label' => 'شحن من المصدر',
            'group' => 'sourcing',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'plane',
            'color' => '#AB7200',
            'bg_light' => 'bg-purple-50/90 text-purple-900 border-purple-200',
        ],
        'sa_received' => [
            'code' => 'sa_received',
            'rank' => 7,
            'label' => 'استلام السعودية',
            'group' => 'sourcing',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'package-check',
            'color' => '#AB7200',
            'bg_light' => 'bg-sky-50/90 text-sky-900 border-sky-200',
        ],
        'ye_in_transit' => [
            'code' => 'ye_in_transit',
            'rank' => 8,
            'label' => 'نقل إلى اليمن',
            'group' => 'sourcing',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'truck',
            'color' => '#AB7200',
            'bg_light' => 'bg-cyan-50/90 text-cyan-900 border-cyan-200',
        ],
        'ye_received' => [
            'code' => 'ye_received',
            'rank' => 9,
            'label' => 'استلام اليمن',
            'group' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'box',
            'color' => '#0C9677',
            'bg_light' => 'bg-teal-50/90 text-teal-900 border-teal-200',
        ],
        'handed_off' => [
            'code' => 'handed_off',
            'rank' => 10,
            'label' => 'جاهز لـ Handoff',
            'group' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'handshake',
            'color' => '#0C9677',
            'bg_light' => 'bg-emerald-50/90 text-emerald-900 border-emerald-200',
        ],
        'delivered' => [
            'code' => 'delivered',
            'rank' => 11,
            'label' => 'تم التسليم',
            'group' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'shield-check',
            'color' => '#0C9677',
            'bg_light' => 'bg-emerald-100/90 text-emerald-950 border-emerald-300',
        ],
    ];

    /**
     * Get full aggregated data for the 11-stage Order Lifecycle Pipeline.
     */
    public function getPipelineSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $stageData = [];
        foreach (self::STAGES as $code => $def) {
            $stageData[$code] = array_merge($def, [
                'count' => 0,
                'value' => 0.0,
                'exception_count' => 0,
                'last_computed_at' => null,
            ]);
        }

        $lastComputedAt = null;
        $totalActiveOrders = 0;

        if (Schema::hasTable('order_lifecycle_stage_views')) {
            // Read bottleneck stage counts from Read Model joined with orders date range
            $views = DB::table('order_lifecycle_stage_views')
                ->join('orders', 'order_lifecycle_stage_views.order_id', '=', 'orders.id')
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->select(
                    'order_lifecycle_stage_views.bottleneck_stage_code',
                    'order_lifecycle_stage_views.is_exception',
                    'order_lifecycle_stage_views.exception_reason',
                    'order_lifecycle_stage_views.computed_at',
                    'orders.grand_total'
                )
                ->get();

            // Auto-trigger idempotent rebuild if views table is empty but orders exist
            if ($views->isEmpty() && DB::table('orders')->exists()) {
                app(OrderLifecycleRebuildService::class)->rebuild();

                $views = DB::table('order_lifecycle_stage_views')
                    ->join('orders', 'order_lifecycle_stage_views.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$startDate, $endDate])
                    ->select(
                        'order_lifecycle_stage_views.bottleneck_stage_code',
                        'order_lifecycle_stage_views.is_exception',
                        'order_lifecycle_stage_views.exception_reason',
                        'order_lifecycle_stage_views.computed_at',
                        'orders.grand_total'
                    )
                    ->get();
            }

            foreach ($views as $v) {
                $code = $v->bottleneck_stage_code;
                if (isset($stageData[$code])) {
                    $stageData[$code]['count']++;
                    $stageData[$code]['value'] += (float) $v->grand_total;

                    if ($v->is_exception) {
                        $stageData[$code]['exception_count']++;
                    }

                    if ($v->computed_at && ($lastComputedAt === null || $v->computed_at > $lastComputedAt)) {
                        $lastComputedAt = $v->computed_at;
                    }
                    if ($v->computed_at && ($stageData[$code]['last_computed_at'] === null || $v->computed_at > $stageData[$code]['last_computed_at'])) {
                        $stageData[$code]['last_computed_at'] = $v->computed_at;
                    }
                }
                $totalActiveOrders++;
            }
        }

        $dataQuality = $this->getUnclassifiedDataQualityInfo();

        return [
            'stages' => array_values($stageData),
            'stages_by_code' => $stageData,
            'total_active_orders' => $totalActiveOrders,
            'last_computed_at' => $lastComputedAt ? Carbon::parse($lastComputedAt)->toIso8601String() : null,
            'formatted_last_computed' => $lastComputedAt ? Carbon::parse($lastComputedAt)->format('Y-m-d H:i:s') : 'غير متاح بعد',
            'data_quality' => $dataQuality,
        ];
    }

    /**
     * Compute data quality exception: unclassified items not projected into item views.
     */
    public function getUnclassifiedDataQualityInfo(): array
    {
        $totalItemsCount = DB::table('order_items')->count();
        $projectedItemsCount = Schema::hasTable('order_item_lifecycle_stage_views')
            ? DB::table('order_item_lifecycle_stage_views')->count()
            : 0;

        $unclassifiedCount = max(0, $totalItemsCount - $projectedItemsCount);

        $unclassifiedItems = [];
        if ($unclassifiedCount > 0 && Schema::hasTable('order_item_lifecycle_stage_views')) {
            $unclassifiedItems = DB::table('order_items')
                ->leftJoin('order_item_lifecycle_stage_views', 'order_items.id', '=', 'order_item_lifecycle_stage_views.order_item_id')
                ->whereNull('order_item_lifecycle_stage_views.order_item_id')
                ->select(
                    'order_items.id as item_id',
                    'order_items.order_id',
                    'order_items.sku',
                    'order_items.name',
                    'order_items.created_at'
                )
                ->limit(20)
                ->get()
                ->toArray();
        }

        return [
            'total_items' => $totalItemsCount,
            'projected_items' => $projectedItemsCount,
            'unclassified_count' => $unclassifiedCount,
            'items' => $unclassifiedItems,
        ];
    }

    /**
     * Get paginated read-only orders list matching a specific stage code.
     */
    public function getOrdersForStage(string $stageCode, int $perPage = 10)
    {
        if (! Schema::hasTable('order_lifecycle_stage_views')) {
            return collect([]);
        }

        return DB::table('order_lifecycle_stage_views')
            ->join('orders', 'order_lifecycle_stage_views.order_id', '=', 'orders.id')
            ->where('order_lifecycle_stage_views.bottleneck_stage_code', $stageCode)
            ->select(
                'orders.id',
                'orders.increment_id',
                'orders.customer_first_name',
                'orders.customer_last_name',
                'orders.grand_total',
                'orders.status as bagisto_status',
                'orders.created_at',
                'order_lifecycle_stage_views.bottleneck_stage_code',
                'order_lifecycle_stage_views.is_mixed_order',
                'order_lifecycle_stage_views.has_imported_items',
                'order_lifecycle_stage_views.has_internal_items',
                'order_lifecycle_stage_views.is_exception',
                'order_lifecycle_stage_views.exception_reason',
                'order_lifecycle_stage_views.computed_at'
            )
            ->orderBy('orders.created_at', 'desc')
            ->paginate($perPage);
    }
}
