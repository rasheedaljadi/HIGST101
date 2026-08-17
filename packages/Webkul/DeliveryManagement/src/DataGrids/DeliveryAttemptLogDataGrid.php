<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryAttemptLogDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_attempt_logs')
            ->leftJoin('delivery_assignments', 'delivery_attempt_logs.delivery_assignment_id', '=', 'delivery_assignments.id')
            ->leftJoin('orders', 'delivery_assignments.order_id', '=', 'orders.id')
            ->leftJoin('admins as couriers', 'delivery_attempt_logs.attempted_by', '=', 'couriers.id')
            ->select(
                'delivery_attempt_logs.id',
                'delivery_attempt_logs.delivery_assignment_id',
                'orders.increment_id as order_increment_id',
                'delivery_attempt_logs.attempt_number',
                'delivery_attempt_logs.status as attempt_status',
                'delivery_attempt_logs.failure_reason',
                'delivery_attempt_logs.notes',
                'couriers.name as courier_name',
                'delivery_attempt_logs.attempted_at',
                'delivery_attempt_logs.created_at'
            );

        $this->addFilter('id', 'delivery_attempt_logs.id');
        $this->addFilter('delivery_assignment_id', 'delivery_attempt_logs.delivery_assignment_id');
        $this->addFilter('failure_reason', 'delivery_attempt_logs.failure_reason');
        $this->addFilter('courier_name', 'couriers.name');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('delivery::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivery_assignment_id',
            'label' => trans('delivery::app.admin.datagrid.assignment-id'),
            'type' => 'integer',
            'filterable' => true,
            'closure' => function ($row) {
                return '<a href="'.route('admin.delivery.assignments.show', $row->delivery_assignment_id).'" class="text-blue-600 hover:underline font-bold">#'.$row->delivery_assignment_id.($row->order_increment_id ? ' ('.$row->order_increment_id.')' : '').'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'attempt_number',
            'label' => trans('delivery::app.admin.failures.attempt-no'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold">#'.$row->attempt_number.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'failure_reason',
            'label' => trans('delivery::app.admin.failures.failure-reason'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                return '<div><div class="font-bold text-gray-800 dark:text-gray-200">'.e($row->failure_reason ?: 'غير محدد').'</div>'.($row->notes ? '<div class="text-xs text-gray-500">'.e($row->notes).'</div>' : '').'</div>';
            },
        ]);

        $this->addColumn([
            'index' => 'courier_name',
            'label' => trans('delivery::app.admin.failures.courier'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                return $row->courier_name ?: '<span class="text-gray-400">غير محدد</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'attempted_at',
            'label' => trans('delivery::app.admin.failures.attempt-time'),
            'type' => 'datetime',
            'sortable' => true,
            'closure' => function ($row) {
                $time = $row->attempted_at ?: $row->created_at;

                return $time ? core()->formatDate($time, 'Y-m-d H:i') : '-';
            },
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => trans('delivery::app.admin.datagrid.view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.delivery.assignments.show', $row->delivery_assignment_id);
            },
        ]);
    }
}
