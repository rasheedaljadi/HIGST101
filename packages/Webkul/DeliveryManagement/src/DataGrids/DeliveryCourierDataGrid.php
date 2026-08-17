<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryCourierDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('admins')
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
            ->where(function ($q) {
                $q->where('roles.name', 'like', '%courier%')
                    ->orWhere('roles.name', 'like', '%مندوب%')
                    ->orWhere('roles.permission_type', 'custom');
            })
            ->select(
                'admins.id',
                'admins.name',
                'admins.email',
                'admins.status',
                'admins.created_at',
                DB::raw('(SELECT COUNT(*) FROM delivery_assignments WHERE delivery_boy_id = admins.id AND status IN ("assigned", "picked_up", "out_for_delivery")) as active_tasks'),
                DB::raw('(SELECT COUNT(*) FROM delivery_assignments WHERE delivery_boy_id = admins.id AND status = "delivered") as completed_tasks'),
                DB::raw('(SELECT COUNT(*) FROM delivery_assignments WHERE delivery_boy_id = admins.id AND status = "delivery_failed") as failed_tasks'),
                DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM delivery_cash_collections WHERE delivery_boy_id = admins.id) as unsettled_cod')
            );

        $this->addFilter('id', 'admins.id');
        $this->addFilter('name', 'admins.name');
        $this->addFilter('email', 'admins.email');
        $this->addFilter('status', 'admins.status');

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
            'index' => 'name',
            'label' => trans('delivery::app.admin.couriers.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-gray-800 dark:text-white">'.$row->name.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'email',
            'label' => trans('delivery::app.admin.couriers.email'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('delivery::app.admin.couriers.status'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->status
                    ? '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-400 text-xs font-semibold">'.trans('delivery::app.admin.couriers.active').'</span>'
                    : '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 text-xs font-semibold">'.trans('delivery::app.admin.couriers.inactive').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'active_tasks',
            'label' => trans('delivery::app.admin.couriers.active-tasks'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">'.$row->active_tasks.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'completed_tasks',
            'label' => trans('delivery::app.admin.couriers.completed-tasks'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold">'.$row->completed_tasks.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'failed_tasks',
            'label' => trans('delivery::app.admin.couriers.failed-tasks'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-bold">'.$row->failed_tasks.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'unsettled_cod',
            'label' => trans('delivery::app.admin.couriers.unsettled-cod'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-amber-600">'.number_format((float) $row->unsettled_cod, 2).' YER</span>';
            },
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('delivery::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.delivery.couriers.edit', $row->id);
            },
        ]);
    }
}
