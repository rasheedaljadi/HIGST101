<?php

namespace Webkul\Fulfillment\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FulfillmentApprovalRequestDataGrid extends DataGrid
{
    /**
     * Set primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('fulfillment_approval_requests')
            ->leftJoin('purchase_orders', 'fulfillment_approval_requests.purchase_order_id', '=', 'purchase_orders.id')
            ->leftJoin('admins as req_admins', 'fulfillment_approval_requests.requested_by', '=', 'req_admins.id')
            ->leftJoin('admins as app_admins', 'fulfillment_approval_requests.approved_by', '=', 'app_admins.id')
            ->select(
                'fulfillment_approval_requests.id as id',
                'fulfillment_approval_requests.purchase_order_id as purchase_order_id',
                'purchase_orders.internal_reference as po_reference',
                'fulfillment_approval_requests.action as action',
                'fulfillment_approval_requests.reason as reason',
                'fulfillment_approval_requests.status as status',
                'fulfillment_approval_requests.created_at as created_at',
                'req_admins.name as requested_by_name',
                'app_admins.name as approved_by_name'
            );

        $this->addFilter('id', 'fulfillment_approval_requests.id');
        $this->addFilter('purchase_order_id', 'fulfillment_approval_requests.purchase_order_id');
        $this->addFilter('action', 'fulfillment_approval_requests.action');
        $this->addFilter('status', 'fulfillment_approval_requests.status');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('fulfillment::app.admin.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'purchase_order_id',
            'label'      => trans('fulfillment::app.admin.datagrid.id') . ' PO',
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $ref = $row->po_reference ? ' (' . htmlspecialchars($row->po_reference) . ')' : '';
                return '<a href="' . route('admin.dropshipping.fulfillment.view', $row->purchase_order_id) . '" class="text-blue-600 hover:underline">#' . $row->purchase_order_id . $ref . '</a>';
            }
        ]);

        $this->addColumn([
            'index'      => 'requested_by_name',
            'label'      => 'Requested By',
            'type'       => 'string',
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'action',
            'label'      => 'Requested Action',
            'type'       => 'string',
            'closure'    => function ($row) {
                return '<span class="capitalize font-mono text-xs">' . htmlspecialchars(str_replace('_', ' ', $row->action)) . '</span>';
            }
        ]);

        $this->addColumn([
            'index'      => 'reason',
            'label'      => 'Reason',
            'type'       => 'string',
            'searchable' => true,
            'closure'    => function ($row) {
                return htmlspecialchars(substr($row->reason, 0, 80)) . (strlen($row->reason) > 80 ? '...' : '');
            }
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('fulfillment::app.admin.datagrid.state'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $status = $row->status;
                
                switch ($status) {
                    case 'pending':
                        return '<span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200 text-xs font-semibold">Pending</span>';
                    case 'approved':
                        return '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 border border-green-200 text-xs font-semibold">Approved</span>';
                    case 'executed':
                        return '<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-semibold">Executed</span>';
                    case 'rejected':
                        return '<span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 border border-red-200 text-xs font-semibold">Rejected</span>';
                    default:
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 text-xs font-semibold">' . htmlspecialchars($status) . '</span>';
                }
            }
        ]);

        $this->addColumn([
            'index'      => 'approved_by_name',
            'label'      => 'Decision By',
            'type'       => 'string',
            'closure'    => function ($row) {
                return $row->approved_by_name ?: '<span class="text-gray-400 italic">Pending</span>';
            }
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => 'Requested At',
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return core()->formatDate($row->created_at, 'Y-m-d H:i:s');
            }
        ]);
    }
}
