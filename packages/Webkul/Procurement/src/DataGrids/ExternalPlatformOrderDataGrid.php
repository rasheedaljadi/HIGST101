<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ExternalPlatformOrderDataGrid extends DataGrid
{
    protected $primaryColumn = 'platform_order_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('external_platform_orders')
            ->leftJoin('supplier_purchase_orders', 'external_platform_orders.supplier_purchase_order_id', '=', 'supplier_purchase_orders.id')
            ->select(
                'external_platform_orders.id as platform_order_id',
                'external_platform_orders.external_order_id',
                'supplier_purchase_orders.purchase_order_number',
                'supplier_purchase_orders.supplier_store_name',
                'external_platform_orders.provider',
                'external_platform_orders.normalized_status',
                'external_platform_orders.normalized_status as raw_normalized_status',
                'external_platform_orders.raw_status',
                'external_platform_orders.tracking_number',
                'external_platform_orders.carrier_name',
                'external_platform_orders.last_synced_at',
                'external_platform_orders.snapshots as raw_snapshots',
                'external_platform_orders.created_at'
            );

        $this->addFilter('platform_order_id', 'external_platform_orders.id');
        $this->addFilter('external_order_id', 'external_platform_orders.external_order_id');
        $this->addFilter('purchase_order_number', 'supplier_purchase_orders.purchase_order_number');
        $this->addFilter('supplier_store_name', 'supplier_purchase_orders.supplier_store_name');
        $this->addFilter('normalized_status', 'external_platform_orders.normalized_status');
        $this->addFilter('tracking_number', 'external_platform_orders.tracking_number');
        $this->addFilter('created_at', 'external_platform_orders.created_at');
        $this->addFilter('last_synced_at', 'external_platform_orders.last_synced_at');

        $status = request()->get('status');
        if (! empty($status) && $status !== 'all') {
            if ($status === 'processing') {
                $queryBuilder->whereIn('external_platform_orders.normalized_status', [
                    'processing',
                    'payment_confirmed',
                ]);
            } elseif ($status === 'completed') {
                $queryBuilder->whereIn('external_platform_orders.normalized_status', [
                    'completed',
                    'delivered',
                ]);
            } else {
                $queryBuilder->where('external_platform_orders.normalized_status', $status);
            }
        }

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'platform_order_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'external_order_id',
            'label' => trans('procurement::app.datagrid.aliexpress-order-id'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->external_order_id ? "<span class=\"font-mono font-semibold text-gray-900 dark:text-gray-100\">{$row->external_order_id}</span>" : '<span class="text-gray-400">-</span>',
        ]);

        $this->addColumn([
            'index' => 'purchase_order_number',
            'label' => trans('procurement::app.datagrid.po-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'supplier_store_name',
            'label' => trans('procurement::app.datagrid.supplier-name'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->supplier_store_name ? "<span class=\"font-medium text-gray-800 dark:text-gray-200\">{$row->supplier_store_name}</span>" : '<span class="text-gray-400">-</span>',
        ]);

        $this->addColumn([
            'index' => 'normalized_status',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $status = $row->raw_normalized_status ?? ($row->normalized_status ?? 'unknown');
                $colors = [
                    'wait_buyer_pay' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 border border-amber-300 dark:border-amber-700',
                    'processing' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'shipped' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                    'submission_failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';

                $statusLabel = match ($status) {
                    'wait_buyer_pay' => trans('procurement::app.platform_orders.tab-wait-buyer-pay'),
                    'processing' => trans('procurement::app.platform_orders.tab-processing'),
                    'shipped' => trans('procurement::app.platform_orders.tab-shipped'),
                    'completed' => trans('procurement::app.platform_orders.tab-completed'),
                    'cancelled' => trans('procurement::app.platform_orders.tab-cancelled'),
                    'submission_failed' => 'فشل الإرسال',
                    default => $status,
                };

                $html = "<div class=\"flex flex-col gap-1 items-start\"><span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$statusLabel}</span>";

                if ($status === 'wait_buyer_pay' && $row->created_at) {
                    $deadline = strtotime($row->created_at) + 7200;
                    $remaining = $deadline - time();
                    if ($remaining > 0) {
                        $hours = str_pad(floor($remaining / 3600), 2, '0', STR_PAD_LEFT);
                        $mins = str_pad(floor(($remaining % 3600) / 60), 2, '0', STR_PAD_LEFT);
                        $secs = str_pad($remaining % 60, 2, '0', STR_PAD_LEFT);
                        $html .= "<span class=\"inline-flex items-center gap-1 text-[11px] font-mono font-semibold text-amber-600 dark:text-amber-400\"><i class=\"icon-clock text-xs\"></i> ⏳ {$hours}:{$mins}:{$secs}</span>";
                    } else {
                        $html .= '<span class="inline-flex items-center text-[10px] font-medium text-rose-500">⌛ '.trans('procurement::app.datagrid.countdown-expired').'</span>';
                    }
                } elseif ($status === 'cancelled') {
                    $html .= '<span class="inline-flex items-center text-[10px] text-gray-400 dark:text-gray-500">⌛ '.trans('procurement::app.datagrid.countdown-expired').'</span>';
                }

                $html .= '</div>';

                return $html;
            },
        ]);

        $this->addColumn([
            'index' => 'tracking_number',
            'label' => trans('procurement::app.datagrid.tracking-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => $row->tracking_number ? "<span class=\"font-mono\">{$row->tracking_number}</span>" : '<span class="text-gray-400">-</span>',
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('procurement::app.datagrid.purchased-at'),
            'type' => 'datetime',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('dropshipping.procurement_v2.submit')) {
            $this->addAction([
                'icon' => 'icon-cart text-2xl text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300',
                'title' => trans('procurement::app.datagrid.reorder'),
                'method' => 'POST',
                'url' => function ($row) {
                    if (($row->raw_normalized_status ?? $row->normalized_status) !== 'cancelled') {
                        return null;
                    }

                    $snapshots = is_string($row->raw_snapshots ?? null) ? json_decode($row->raw_snapshots, true) : ($row->raw_snapshots ?? []);
                    if (! empty($snapshots['is_reordered'])) {
                        return null;
                    }

                    return route('admin.procurement.platform_orders.reorder', $row->platform_order_id);
                },
            ]);

            $this->addAction([
                'icon' => 'icon-cancel text-2xl text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300',
                'title' => trans('procurement::app.datagrid.cancel-order'),
                'method' => 'POST',
                'url' => fn ($row) => ($row->raw_normalized_status ?? $row->normalized_status) === 'wait_buyer_pay'
                    ? route('admin.procurement.platform_orders.cancel', $row->platform_order_id)
                    : null,
            ]);

            $this->addAction([
                'icon' => 'icon-delete text-2xl text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300',
                'title' => trans('procurement::app.datagrid.delete'),
                'method' => 'DELETE',
                'url' => fn ($row) => in_array($row->raw_normalized_status ?? $row->normalized_status, ['cancelled', 'submission_failed'], true)
                    ? route('admin.procurement.platform_orders.destroy', $row->platform_order_id)
                    : null,
            ]);
        }
    }
}
