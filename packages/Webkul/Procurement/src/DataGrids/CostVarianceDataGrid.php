<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CostVarianceDataGrid extends DataGrid
{
    protected $primaryColumn = 'spo_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('supplier_purchase_orders')
            ->leftJoin('procurement_batches', 'supplier_purchase_orders.batch_id', '=', 'procurement_batches.id')
            ->where('supplier_purchase_orders.state', 'cost_variance_review')
            ->select(
                'supplier_purchase_orders.id as spo_id',
                'supplier_purchase_orders.purchase_order_number',
                'procurement_batches.batch_number',
                'supplier_purchase_orders.supplier_store_name',
                'supplier_purchase_orders.expected_total',
                'supplier_purchase_orders.actual_total',
                'supplier_purchase_orders.cost_variance_amount',
                'supplier_purchase_orders.state',
                'supplier_purchase_orders.created_at'
            );

        $this->addFilter('spo_id', 'supplier_purchase_orders.id');
        $this->addFilter('purchase_order_number', 'supplier_purchase_orders.purchase_order_number');
        $this->addFilter('batch_number', 'procurement_batches.batch_number');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'spo_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'purchase_order_number',
            'label' => trans('procurement::app.datagrid.po-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $url = route('admin.procurement.supplier_orders.view', $row->spo_id);

                return "<a href=\"{$url}\" class=\"text-blue-600 font-semibold hover:underline\">{$row->purchase_order_number}</a>";
            },
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
            'index' => 'expected_total',
            'label' => trans('procurement::app.datagrid.expected-cost'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => '$'.number_format((float) $row->expected_total, 2),
        ]);

        $this->addColumn([
            'index' => 'actual_total',
            'label' => trans('procurement::app.datagrid.actual-cost'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => '$'.number_format((float) $row->actual_total, 2),
        ]);

        $this->addColumn([
            'index' => 'cost_variance_amount',
            'label' => trans('procurement::app.datagrid.variance'),
            'type' => 'decimal',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $val = (float) $row->cost_variance_amount;
                $cls = $val > 0 ? 'text-rose-600 font-semibold' : 'text-emerald-600 font-semibold';

                return "<span class=\"{$cls}\">".($val > 0 ? '+' : '').'$'.number_format($val, 2).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'state',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">'.trans('procurement::app.states.cost_variance_review').'</span>',
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-view text-2xl text-gray-700 hover:text-blue-600 dark:text-gray-200 dark:hover:text-blue-400',
            'title' => trans('procurement::app.datagrid.view'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.procurement.supplier_orders.view', $row->spo_id),
        ]);
    }
}
