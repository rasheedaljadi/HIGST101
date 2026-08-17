<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryAuditLogDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_audit_logs')
            ->leftJoin('admins', 'delivery_audit_logs.user_id', '=', 'admins.id')
            ->select(
                'delivery_audit_logs.id',
                'delivery_audit_logs.action',
                'delivery_audit_logs.entity_type',
                'delivery_audit_logs.entity_id',
                'delivery_audit_logs.delivery_assignment_id',
                'delivery_audit_logs.user_name',
                'admins.name as admin_name',
                'delivery_audit_logs.reason',
                'delivery_audit_logs.old_values',
                'delivery_audit_logs.new_values',
                'delivery_audit_logs.ip_address',
                'delivery_audit_logs.created_at'
            );

        $this->addFilter('id', 'delivery_audit_logs.id');
        $this->addFilter('action', 'delivery_audit_logs.action');
        $this->addFilter('entity_type', 'delivery_audit_logs.entity_type');

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
            'index' => 'user_name',
            'label' => trans('delivery::app.admin.audit-logs.actor'),
            'type' => 'string',
            'searchable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-gray-800 dark:text-white">'.($row->admin_name ?: $row->user_name ?: 'System').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'action',
            'label' => trans('delivery::app.admin.audit-logs.action'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded text-xs font-mono bg-purple-50 text-purple-700">'.$row->action.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'entity_type',
            'label' => trans('delivery::app.admin.audit-logs.entity'),
            'type' => 'string',
            'closure' => function ($row) {
                return '<span class="text-xs font-medium">'.$row->entity_type.' #'.($row->entity_id ?: $row->delivery_assignment_id).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'reason',
            'label' => trans('delivery::app.admin.audit-logs.reason'),
            'type' => 'string',
            'searchable' => true,
            'closure' => function ($row) {
                return $row->reason ? '<span class="text-xs text-gray-600 dark:text-gray-300">'.$row->reason.'</span>' : '<span class="text-gray-400">-</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'ip_address',
            'label' => trans('delivery::app.admin.audit-logs.ip-address'),
            'type' => 'string',
            'closure' => function ($row) {
                return '<span class="font-mono text-xs text-gray-400">'.$row->ip_address.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('delivery::app.admin.audit-logs.timestamp'),
            'type' => 'datetime',
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->created_at, 'Y-m-d H:i:s');
            },
        ]);
    }
}
