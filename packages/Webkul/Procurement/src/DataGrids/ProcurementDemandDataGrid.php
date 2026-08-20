<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProcurementDemandDataGrid extends DataGrid
{
    protected $primaryColumn = 'demand_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('procurement_demands')
            ->leftJoin('orders', 'procurement_demands.order_id', '=', 'orders.id')
            ->leftJoin('order_items', 'procurement_demands.order_item_id', '=', 'order_items.id')
            ->select(
                'procurement_demands.id as demand_id',
                'orders.increment_id as order_increment_id',
                'procurement_demands.order_id',
                'procurement_demands.order_item_id',
                'procurement_demands.provider',
                'procurement_demands.supplier_store_name',
                'procurement_demands.supplier_product_id',
                'procurement_demands.supplier_sku_id',
                'procurement_demands.qty_requested',
                'procurement_demands.qty_covered_by_local',
                'procurement_demands.qty_required_external',
                'procurement_demands.qty_batched',
                'procurement_demands.qty_received_good',
                'procurement_demands.state',
                'procurement_demands.supplier_currency_code',
                'procurement_demands.created_at'
            );

        $this->addFilter('demand_id', 'procurement_demands.id');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('state', 'procurement_demands.state');
        $this->addFilter('provider', 'procurement_demands.provider');
        $this->addFilter('supplier_store_name', 'procurement_demands.supplier_store_name');
        $this->addFilter('supplier_sku_id', 'procurement_demands.supplier_sku_id');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'demand_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_increment_id',
            'label' => trans('procurement::app.datagrid.order-id'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'supplier_store_name',
            'label' => trans('procurement::app.datagrid.supplier-store'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'supplier_sku_id',
            'label' => trans('procurement::app.datagrid.supplier-sku'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'qty_required_external',
            'label' => trans('procurement::app.datagrid.deficit-qty'),
            'type' => 'integer',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'qty_batched',
            'label' => trans('procurement::app.datagrid.batched-qty'),
            'type' => 'integer',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'state',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $colors = [
                    'open_for_batching' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    'batched' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'ordered' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                    'fulfilled' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'locally_covered' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$row->state] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                $label = trans("procurement::app.states.{$row->state}") ?: $row->state;

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$label}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('procurement::app.datagrid.created-at'),
            'type' => 'date',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);
    }
}
