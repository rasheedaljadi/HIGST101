<?php

namespace Webkul\Procurement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProcurementBatchDataGrid extends DataGrid
{
    protected $primaryColumn = 'batch_id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('procurement_batches')
            ->leftJoin('admins as creator', 'procurement_batches.created_by', '=', 'creator.id')
            ->select(
                'procurement_batches.id as batch_id',
                'procurement_batches.batch_number',
                'procurement_batches.provider',
                'procurement_batches.currency_code',
                'procurement_batches.state',
                'procurement_batches.expected_total_cost',
                'procurement_batches.actual_total_cost',
                'procurement_batches.cost_variance_amount',
                'creator.name as creator_name',
                'procurement_batches.created_at',
                'procurement_batches.approved_at'
            );

        $this->addFilter('batch_id', 'procurement_batches.id');
        $this->addFilter('batch_number', 'procurement_batches.batch_number');
        $this->addFilter('state', 'procurement_batches.state');
        $this->addFilter('provider', 'procurement_batches.provider');
        $this->addFilter('creator_name', 'creator.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'batch_id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index' => 'batch_number',
            'label' => trans('procurement::app.datagrid.batch-number'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                $url = route('admin.procurement.batches.view', $row->batch_id);

                return "<a href=\"{$url}\" class=\"text-blue-600 font-semibold hover:underline\">{$row->batch_number}</a>";
            },
        ]);

        $this->addColumn([
            'index' => 'provider',
            'label' => trans('procurement::app.datagrid.provider'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
        ]);

        if (bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')) {
            $this->addColumn([
                'index' => 'expected_total_cost',
                'label' => trans('procurement::app.datagrid.expected-cost'),
                'type' => 'decimal',
                'searchable' => false,
                'sortable' => true,
                'filterable' => true,
                'closure' => fn ($row) => '$'.number_format((float) $row->expected_total_cost, 2),
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
                    if (abs($val) < 0.001) {
                        return '<span class="text-gray-500">$0.00</span>';
                    }
                    $cls = $val > 0 ? 'text-rose-600 font-semibold' : 'text-emerald-600 font-semibold';

                    return "<span class=\"{$cls}\">".($val > 0 ? '+' : '').'$'.number_format($val, 2).'</span>';
                },
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
                    'ready_for_review' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                    'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                    'submitted_to_provider' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                    'awaiting_manual_payment' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                    'payment_declared' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300',
                    'cost_variance_review' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                    'supplier_processing' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
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
