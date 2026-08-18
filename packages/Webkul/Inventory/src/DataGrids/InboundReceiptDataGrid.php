<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InboundReceiptDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('inbound_receipt_manifests')
            ->leftJoin('inventory_sources as dest_src', 'inbound_receipt_manifests.destination_inventory_source_id', '=', 'dest_src.id')
            ->leftJoin('inventory_transfer_manifests as trf', 'inbound_receipt_manifests.inventory_transfer_manifest_id', '=', 'trf.id')
            ->leftJoin('admins as receiver', 'inbound_receipt_manifests.received_by_admin_id', '=', 'receiver.id')
            ->select(
                'inbound_receipt_manifests.id as id',
                'inbound_receipt_manifests.receipt_number as receipt_number',
                'trf.manifest_number as transfer_manifest_number',
                'inbound_receipt_manifests.external_reference as external_reference',
                'dest_src.name as destination_name',
                'inbound_receipt_manifests.status as status',
                'inbound_receipt_manifests.total_received_good as total_received_good',
                'inbound_receipt_manifests.total_received_damaged as total_received_damaged',
                'inbound_receipt_manifests.total_received_missing as total_received_missing',
                'receiver.name as receiver_name',
                'inbound_receipt_manifests.created_at as created_at'
            );

        $this->addFilter('id', 'inbound_receipt_manifests.id');
        $this->addFilter('receipt_number', 'inbound_receipt_manifests.receipt_number');
        $this->addFilter('transfer_manifest_number', 'trf.manifest_number');
        $this->addFilter('status', 'inbound_receipt_manifests.status');
        $this->addFilter('created_at', 'inbound_receipt_manifests.created_at');

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
            'index' => 'id',
            'label' => trans('inventory::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'receipt_number',
            'label' => trans('inventory::app.admin.datagrid.receipt_number'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-emerald-600 dark:text-emerald-400">'.$row->receipt_number.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'transfer_manifest_number',
            'label' => trans('inventory::app.admin.datagrid.transfer_manifest'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->transfer_manifest_number ?: '<span class="text-gray-400">-</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'destination_name',
            'label' => trans('inventory::app.admin.datagrid.destination'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'total_received_good',
            'label' => trans('inventory::app.admin.datagrid.good_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="text-emerald-600 font-bold">'.number_format($row->total_received_good).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'total_received_damaged',
            'label' => trans('inventory::app.admin.datagrid.damaged_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->total_received_damaged > 0
                    ? '<span class="text-rose-600 font-bold">'.number_format($row->total_received_damaged).'</span>'
                    : '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'total_received_missing',
            'label' => trans('inventory::app.admin.datagrid.missing_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->total_received_missing > 0
                    ? '<span class="text-amber-600 font-bold">'.number_format($row->total_received_missing).'</span>'
                    : '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'receiver_name',
            'label' => trans('inventory::app.admin.datagrid.received_by'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('inventory::app.admin.datagrid.created_at'),
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->created_at, 'Y-m-d H:i');
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-view',
            'title' => trans('inventory::app.admin.datagrid.view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.inventory.receipts.show', $row->id);
            },
        ]);
    }
}
