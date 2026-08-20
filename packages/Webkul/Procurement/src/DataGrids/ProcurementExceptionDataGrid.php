<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProcurementExceptionDataGrid extends DataGrid
{
    protected $primaryColumn = 'log_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('procurement_audit_logs')
            ->whereIn('action', [
                'internal_stock_exception',
                'cost_variance_detected',
                'cost_variance_rejected',
                'supplier_order_failed',
                'batch_rejected',
            ])
            ->select(
                'procurement_audit_logs.id as log_id',
                'procurement_audit_logs.action',
                'procurement_audit_logs.auditable_type',
                'procurement_audit_logs.auditable_id',
                'procurement_audit_logs.details',
                'procurement_audit_logs.created_at'
            );

        $this->addFilter('log_id', 'procurement_audit_logs.id');
        $this->addFilter('action', 'procurement_audit_logs.action');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'log_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'action',
            'label' => trans('procurement::app.datagrid.exception-type'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $colors = [
                    'internal_stock_exception' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    'cost_variance_detected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                    'cost_variance_rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                ];
                $color = $colors[$row->action] ?? 'bg-gray-100 text-gray-800';

                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$color}\">{$row->action}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'details',
            'label' => trans('procurement::app.datagrid.details'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                $details = is_string($row->details) ? json_decode($row->details, true) : $row->details;
                $summary = $details['reason'] ?? $details['notes'] ?? json_encode($details);

                return "<span class=\"text-xs text-gray-600 dark:text-gray-400\">{$summary}</span>";
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('procurement::app.datagrid.created-at'),
            'type' => 'datetime',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
        ]);
    }
}
