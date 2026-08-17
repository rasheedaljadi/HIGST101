<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryPointDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_points')
            ->select(
                'delivery_points.id',
                'delivery_points.code',
                'delivery_points.name',
                'delivery_points.state_code as governorate',
                'delivery_points.city',
                'delivery_points.address',
                'delivery_points.contact_name',
                'delivery_points.contact_phone',
                'delivery_points.max_capacity',
                'delivery_points.is_active',
                DB::raw('(SELECT COUNT(*) FROM delivery_assignments WHERE delivery_point_id = delivery_points.id AND status = "arrived_at_point") as current_shipments'),
                DB::raw('(SELECT COUNT(*) FROM admins WHERE delivery_point_id = delivery_points.id) as linked_staff')
            );

        $this->addFilter('id', 'delivery_points.id');
        $this->addFilter('code', 'delivery_points.code');
        $this->addFilter('name', 'delivery_points.name');
        $this->addFilter('governorate', 'delivery_points.state_code');
        $this->addFilter('is_active', 'delivery_points.is_active');

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
            'index' => 'code',
            'label' => trans('delivery::app.admin.points.code'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">'.$row->code.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('delivery::app.admin.points.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-gray-800 dark:text-white">'.$row->name.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'governorate',
            'label' => trans('delivery::app.admin.points.governorate'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'contact',
            'label' => trans('delivery::app.admin.points.contact-name'),
            'type' => 'string',
            'closure' => function ($row) {
                return '<div><div class="font-medium text-xs">'.$row->contact_name.'</div><div class="text-xs text-gray-400">'.$row->contact_phone.'</div></div>';
            },
        ]);

        $this->addColumn([
            'index' => 'current_shipments',
            'label' => trans('delivery::app.admin.points.current-shipments'),
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-cyan-50 text-cyan-700 font-bold">'.$row->current_shipments.' / '.$row->max_capacity.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'linked_staff',
            'label' => 'الموظفون المرتبطون',
            'type' => 'integer',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-bold">'.$row->linked_staff.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('delivery::app.admin.points.status'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->is_active
                    ? '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-400 text-xs font-semibold">'.trans('delivery::app.admin.datagrid.active').'</span>'
                    : '<span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 text-xs font-semibold">'.trans('delivery::app.admin.datagrid.inactive').'</span>';
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
                return route('admin.delivery.points.edit', $row->id);
            },
        ]);
    }
}
