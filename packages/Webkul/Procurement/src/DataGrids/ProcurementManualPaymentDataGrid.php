<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProcurementManualPaymentDataGrid extends DataGrid
{
    protected $primaryColumn = 'payment_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('procurement_manual_payment_confirmations')
            ->leftJoin('supplier_purchase_orders', 'procurement_manual_payment_confirmations.supplier_purchase_order_id', '=', 'supplier_purchase_orders.id')
            ->leftJoin('admins', 'procurement_manual_payment_confirmations.confirmed_by', '=', 'admins.id')
            ->select(
                'procurement_manual_payment_confirmations.id as payment_id',
                'procurement_manual_payment_confirmations.external_reference',
                'supplier_purchase_orders.purchase_order_number',
                'procurement_manual_payment_confirmations.declared_total',
                'procurement_manual_payment_confirmations.currency_code',
                'procurement_manual_payment_confirmations.state',
                'admins.name as confirmed_by_name',
                'procurement_manual_payment_confirmations.confirmed_at',
                'procurement_manual_payment_confirmations.created_at'
            );

        $this->addFilter('payment_id', 'procurement_manual_payment_confirmations.id');
        $this->addFilter('external_reference', 'procurement_manual_payment_confirmations.external_reference');
        $this->addFilter('purchase_order_number', 'supplier_purchase_orders.purchase_order_number');
        $this->addFilter('state', 'procurement_manual_payment_confirmations.state');
        $this->addFilter('confirmed_by_name', 'admins.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'payment_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'external_reference',
            'label' => trans('procurement::app.datagrid.payment-reference'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => fn ($row) => "<span class=\"font-mono font-semibold text-gray-900 dark:text-gray-100\">{$row->external_reference}</span>",
        ]);

        $this->addColumn([
            'index' => 'purchase_order_number',
            'label' => trans('procurement::app.datagrid.po-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')) {
            $this->addColumn([
                'index' => 'declared_total',
                'label' => trans('procurement::app.datagrid.declared-total'),
                'type' => 'decimal',
                'searchable' => false,
                'sortable' => true,
                'filterable' => true,
                'closure' => fn ($row) => '$'.number_format((float) $row->declared_total, 2),
            ]);
        }

        $this->addColumn([
            'index' => 'confirmed_by_name',
            'label' => trans('procurement::app.datagrid.confirmed-by'),
            'type' => 'string',
            'searchable' => true,
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
                    'pending_verification' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    'verified' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                ];
                $color = $colors[$row->state] ?? 'bg-gray-100 text-gray-800';

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$row->state}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'confirmed_at',
            'label' => trans('procurement::app.datagrid.confirmed-at'),
            'type' => 'datetime',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);
    }
}
