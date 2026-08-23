<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class SupplierPurchaseOrderDataGrid extends DataGrid
{
    protected $primaryColumn = 'spo_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('supplier_purchase_orders')
            ->leftJoin('procurement_batches', 'supplier_purchase_orders.batch_id', '=', 'procurement_batches.id')
            ->select(
                'supplier_purchase_orders.id as spo_id',
                'supplier_purchase_orders.purchase_order_number',
                'procurement_batches.batch_number',
                'supplier_purchase_orders.supplier_store_name',
                'supplier_purchase_orders.expected_total',
                'supplier_purchase_orders.actual_total',
                'supplier_purchase_orders.cost_variance_amount',
                'supplier_purchase_orders.state',
                'supplier_purchase_orders.state as raw_state',
                'supplier_purchase_orders.payment_state',
                'supplier_purchase_orders.created_at'
            );

        $this->addFilter('spo_id', 'supplier_purchase_orders.id');
        $this->addFilter('purchase_order_number', 'supplier_purchase_orders.purchase_order_number');
        $this->addFilter('batch_number', 'procurement_batches.batch_number');
        $this->addFilter('state', 'supplier_purchase_orders.state');
        $this->addFilter('payment_state', 'supplier_purchase_orders.payment_state');
        $this->addFilter('supplier_store_name', 'supplier_purchase_orders.supplier_store_name');

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
            'index' => 'batch_number',
            'label' => trans('procurement::app.datagrid.batch-number'),
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
        ]);

        if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')) {
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
        }

        $this->addColumn([
            'index' => 'state',
            'label' => trans('procurement::app.datagrid.state'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $colors = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    'ready_to_submit' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'awaiting_manual_payment' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                    'payment_declared' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
                    'cost_variance_review' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                    'supplier_processing' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'supplier_shipped' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                    'closed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$row->state] ?? 'bg-gray-100 text-gray-800';
                $label = trans("procurement::app.states.{$row->state}") ?: $row->state;

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$label}</span>";
            },
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
        $this->addAction([
            'icon' => 'icon-view text-2xl text-gray-700 hover:text-blue-600 dark:text-gray-200 dark:hover:text-blue-400',
            'title' => trans('procurement::app.datagrid.view'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.procurement.supplier_orders.view', $row->spo_id),
        ]);

        if (bouncer()->hasPermission('dropshipping.procurement_v2.submit')) {
            $this->addAction([
                'icon' => 'icon-cancel text-2xl text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300',
                'title' => trans('procurement::app.datagrid.cancel-order'),
                'method' => 'POST',
                'url' => fn ($row) => ! in_array($row->raw_state ?? $row->state, ['cancelled', 'closed', 'received_in_full'], true)
                    ? route('admin.procurement.supplier_orders.cancel', $row->spo_id)
                    : null,
            ]);
        }
    }
}
