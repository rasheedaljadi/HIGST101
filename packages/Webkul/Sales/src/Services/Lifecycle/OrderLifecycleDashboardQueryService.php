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
            'short' => 'جديد',
            'group' => 'customer',
            'tone' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'shopping-bag',
            'owner' => 'فريق الطلبات',
            'description' => 'طلبات جديدة وصلت وتنتظر التحقق الأولي من بيانات العميل والمخزون.',
            'avg' => '18 د',
            'color' => '#253691',
            'bg_light' => 'bg-blue-50/90 text-blue-900 border-blue-200',
        ],
        'payment_pending' => [
            'code' => 'payment_pending',
            'rank' => 2,
            'label' => 'بانتظار الدفع',
            'short' => 'الدفع',
            'group' => 'customer',
            'tone' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'wallet',
            'owner' => 'المدفوعات',
            'description' => 'الطلبات التي تتطلب تأكيد الدفع أو اختيار طريقة دفع بديلة.',
            'avg' => '42 د',
            'color' => '#253691',
            'bg_light' => 'bg-blue-50/90 text-blue-900 border-blue-200',
        ],
        'confirmed' => [
            'code' => 'confirmed',
            'rank' => 3,
            'label' => 'تم التأكيد',
            'short' => 'التأكيد',
            'group' => 'customer',
            'tone' => 'customer',
            'group_label' => 'رحلة العميل',
            'icon' => 'badge-check',
            'owner' => 'العمليات',
            'description' => 'طلبات مؤكدة وجاهزة لتحديد مسارها: داخلي، دروبشوبنج، أو مختلط.',
            'avg' => '1.4 س',
            'color' => '#253691',
            'bg_light' => 'bg-indigo-50/90 text-indigo-900 border-indigo-200',
        ],
        'sourcing_required' => [
            'code' => 'sourcing_required',
            'rank' => 4,
            'label' => 'يحتاج توريداً',
            'short' => 'التوريد',
            'group' => 'sourcing',
            'tone' => 'supply',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'receipt-text',
            'owner' => 'المشتريات',
            'description' => 'طلبات مستوردة لا يوجد لها رصيد محلي مؤكد بعد، وتحتاج قرار توريد.',
            'avg' => '3.2 س',
            'color' => '#AB7200',
            'bg_light' => 'bg-amber-50/90 text-amber-900 border-amber-200',
        ],
        'po_created' => [
            'code' => 'po_created',
            'rank' => 5,
            'label' => 'أمر شراء منشأ',
            'short' => 'PO',
            'group' => 'sourcing',
            'tone' => 'risk',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'file-check',
            'owner' => 'المشتريات',
            'description' => 'أوامر شراء أنشئت وتحتاج تأكيد المورد أو تحديث حالة الشحن.',
            'avg' => '8.1 س',
            'color' => '#AB7200',
            'bg_light' => 'bg-amber-100/80 text-amber-950 border-amber-300',
        ],
        'supplier_shipped' => [
            'code' => 'supplier_shipped',
            'rank' => 6,
            'label' => 'شحن من المصدر',
            'short' => 'شحن المصدر',
            'group' => 'sourcing',
            'tone' => 'supply',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'plane',
            'owner' => 'التوريد',
            'description' => 'شحنات غادرت المورد وتتابَع قبل الوصول إلى مركز الاستلام السعودي.',
            'avg' => '1.8 ي',
            'color' => '#AB7200',
            'bg_light' => 'bg-purple-50/90 text-purple-900 border-purple-200',
        ],
        'sa_received' => [
            'code' => 'sa_received',
            'rank' => 7,
            'label' => 'استلام السعودية',
            'short' => 'استلام SA',
            'group' => 'sourcing',
            'tone' => 'supply',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'package-check',
            'owner' => 'مركز السعودية',
            'description' => 'بضاعة وصلت إلى السعودية وتنتظر الاستلام والفحص وتوثيق النقص أو التلف.',
            'avg' => '5.6 س',
            'color' => '#AB7200',
            'bg_light' => 'bg-sky-50/90 text-sky-900 border-sky-200',
        ],
        'ye_in_transit' => [
            'code' => 'ye_in_transit',
            'rank' => 8,
            'label' => 'نقل إلى اليمن',
            'short' => 'نقل YE',
            'group' => 'sourcing',
            'tone' => 'supply',
            'group_label' => 'سلسلة التوريد والمخازن',
            'icon' => 'truck',
            'owner' => 'النقل',
            'description' => 'مانيفستات خرجت من السعودية في طريقها إلى اليمن، ولا تعد قابلة للتسليم بعد.',
            'avg' => '1.2 ي',
            'color' => '#AB7200',
            'bg_light' => 'bg-cyan-50/90 text-cyan-900 border-cyan-200',
        ],
        'ye_received' => [
            'code' => 'ye_received',
            'rank' => 9,
            'label' => 'استلام اليمن',
            'short' => 'استلام YE',
            'group' => 'local',
            'tone' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'box',
            'owner' => 'مخزن اليمن',
            'description' => 'رصيد مستلم فعلياً في اليمن وأصبح مؤهلاً للتخصيص للطلبات.',
            'avg' => '2.7 س',
            'color' => '#0C9677',
            'bg_light' => 'bg-teal-50/90 text-teal-900 border-teal-200',
        ],
        'handed_off' => [
            'code' => 'handed_off',
            'rank' => 10,
            'label' => 'جاهز لـ Handoff',
            'short' => 'Handoff',
            'group' => 'local',
            'tone' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'handshake',
            'owner' => 'التسليم',
            'description' => 'طلبات تم تخصيصها ويمكن تسليمها للمندوب أو لنقطة التسليم.',
            'avg' => '58 د',
            'color' => '#0C9677',
            'bg_light' => 'bg-emerald-50/90 text-emerald-900 border-emerald-200',
        ],
        'delivered' => [
            'code' => 'delivered',
            'rank' => 11,
            'label' => 'تم التسليم',
            'short' => 'تم التسليم',
            'group' => 'local',
            'tone' => 'local',
            'group_label' => 'التنفيذ والتسليم المحلي',
            'icon' => 'shield-check',
            'owner' => 'فريق التسليم',
            'description' => 'طلبات أغلقت بتسليم ناجح خلال الفترة المحددة.',
            'avg' => '1.7 ي',
            'color' => '#0C9677',
            'bg_light' => 'bg-emerald-100/90 text-emerald-950 border-emerald-300',
        ],
    ];

    /**
     * Get full aggregated data for the 11-stage Order Lifecycle Pipeline.
     */
    public function getPipelineSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $stageData = [];
        foreach (self::STAGES as $code => $def) {
            $stageData[$code] = array_merge($def, [
                'count' => 0,
                'value' => 0.0,
                'exception_count' => 0,
                'last_computed_at' => null,
                'orders' => [],
            ]);
        }

        $lastComputedAt = null;
        $totalActiveOrders = 0;

        if (Schema::hasTable('order_lifecycle_stage_views')) {
            $query = DB::table('order_lifecycle_stage_views')
                ->join('orders', 'order_lifecycle_stage_views.order_id', '=', 'orders.id');

            if ($startDate !== null && $endDate !== null) {
                $query->whereBetween('orders.created_at', [$startDate, $endDate]);
            }

            $views = $query->select(
                'order_lifecycle_stage_views.order_id',
                'order_lifecycle_stage_views.bottleneck_stage_code',
                'order_lifecycle_stage_views.is_exception',
                'order_lifecycle_stage_views.exception_reason',
                'order_lifecycle_stage_views.computed_at',
                'orders.id as order_db_id',
                DB::raw(Schema::hasColumn('orders', 'increment_id') ? 'orders.increment_id' : 'orders.id as increment_id'),
                'orders.grand_total'
            )->get();

            // Auto-trigger idempotent rebuild if views table is empty but orders exist
            if ($views->isEmpty() && DB::table('orders')->exists()) {
                app(OrderLifecycleRebuildService::class)->rebuild();

                $query = DB::table('order_lifecycle_stage_views')
                    ->join('orders', 'order_lifecycle_stage_views.order_id', '=', 'orders.id');

                if ($startDate !== null && $endDate !== null) {
                    $query->whereBetween('orders.created_at', [$startDate, $endDate]);
                }

                $views = $query->select(
                    'order_lifecycle_stage_views.order_id',
                    'order_lifecycle_stage_views.bottleneck_stage_code',
                    'order_lifecycle_stage_views.is_exception',
                    'order_lifecycle_stage_views.exception_reason',
                    'order_lifecycle_stage_views.computed_at',
                    'orders.id as order_db_id',
                    DB::raw(Schema::hasColumn('orders', 'increment_id') ? 'orders.increment_id' : 'orders.id as increment_id'),
                    'orders.grand_total'
                )->get();
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

                    $stageData[$code]['orders'][] = [
                        'id' => $v->order_db_id,
                        'number' => '#'.($v->increment_id ?? $v->order_db_id),
                        'value' => (float) $v->grand_total,
                        'is_exception' => (bool) $v->is_exception,
                        'status_label' => $v->is_exception ? 'تحتاج متابعة' : 'ضمن SLA',
                        'view_url' => route('admin.sales.orders.view', $v->order_db_id),
                    ];
                }
                $totalActiveOrders++;
            }
        }

        $activePipelineCount = 0;
        foreach (['new', 'payment_pending', 'confirmed', 'sourcing_required', 'po_created', 'supplier_shipped', 'sa_received', 'ye_in_transit', 'ye_received', 'handed_off'] as $activeStageCode) {
            $activePipelineCount += $stageData[$activeStageCode]['count'] ?? 0;
        }
        $deliveredCount = $stageData['delivered']['count'] ?? 0;
        $sourcingDecisionsCount = $stageData['sourcing_required']['count'] ?? 0;
        $totalOrdersAll = $activePipelineCount + $deliveredCount;
        $deliveryRate = $totalOrdersAll > 0 ? round(($deliveredCount / $totalOrdersAll) * 100, 1) : 0;

        $dataQuality = $this->getUnclassifiedDataQualityInfo();

        return [
            'stages' => array_values($stageData),
            'stages_by_code' => $stageData,
            'total_active_orders' => $totalActiveOrders,
            'active_pipeline_count' => $activePipelineCount,
            'sourcing_decisions_count' => $sourcingDecisionsCount,
            'delivered_count' => $deliveredCount,
            'delivery_rate' => $deliveryRate,
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
                DB::raw(Schema::hasColumn('orders', 'increment_id') ? 'orders.increment_id' : 'orders.id as increment_id'),
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
