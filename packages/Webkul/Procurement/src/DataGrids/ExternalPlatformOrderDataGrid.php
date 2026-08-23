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
                'external_platform_orders.provider',
                'external_platform_orders.normalized_status',
                'external_platform_orders.raw_status',
                'external_platform_orders.tracking_number',
                'external_platform_orders.carrier_name',
                'external_platform_orders.last_synced_at',
                'external_platform_orders.created_at'
            );

        $this->addFilter('platform_order_id', 'external_platform_orders.id');
        $this->addFilter('external_order_id', 'external_platform_orders.external_order_id');
        $this->addFilter('purchase_order_number', 'supplier_purchase_orders.purchase_order_number');
        $this->addFilter('normalized_status', 'external_platform_orders.normalized_status');
        $this->addFilter('tracking_number', 'external_platform_orders.tracking_number');

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
            'closure' => fn ($row) => "<span class=\"font-mono font-semibold text-gray-900 dark:text-gray-100\">{$row->external_order_id}</span>",
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
            'index' => 'normalized_status',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $colors = [
                    'wait_buyer_pay' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                    'processing' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'shipped' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$row->normalized_status] ?? 'bg-gray-100 text-gray-800';

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$row->normalized_status}</span>";
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
            'index' => 'last_synced_at',
            'label' => trans('procurement::app.datagrid.last-synced-at'),
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
                'icon' => 'icon-refresh text-2xl',
                'title' => trans('procurement::app.datagrid.sync'),
                'method' => 'POST',
                'url' => fn ($row) => route('admin.procurement.platform_orders.sync', $row->platform_order_id),
            ]);
        }
    }
}
