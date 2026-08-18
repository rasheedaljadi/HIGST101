<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryTransferDataGrid extends DataGrid
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
        $queryBuilder = DB::table('inventory_transfer_manifests')
            ->leftJoin('inventory_sources as source_src', 'inventory_transfer_manifests.source_inventory_source_id', '=', 'source_src.id')
            ->leftJoin('inventory_sources as dest_src', 'inventory_transfer_manifests.destination_inventory_source_id', '=', 'dest_src.id')
            ->leftJoin('admins as creator', 'inventory_transfer_manifests.created_by_admin_id', '=', 'creator.id')
            ->select(
                'inventory_transfer_manifests.id as id',
                'inventory_transfer_manifests.manifest_number as manifest_number',
                'source_src.name as source_name',
                'dest_src.name as destination_name',
                'inventory_transfer_manifests.status as status',
                'inventory_transfer_manifests.tracking_number as tracking_number',
                'inventory_transfer_manifests.carrier_name as carrier_name',
                'inventory_transfer_manifests.total_packages as total_packages',
                'inventory_transfer_manifests.total_items_count as total_items_count',
                'inventory_transfer_manifests.dispatched_at as dispatched_at',
                'inventory_transfer_manifests.received_at as received_at',
                'creator.name as creator_name',
                'inventory_transfer_manifests.created_at as created_at'
            );

        $this->addFilter('id', 'inventory_transfer_manifests.id');
        $this->addFilter('manifest_number', 'inventory_transfer_manifests.manifest_number');
        $this->addFilter('status', 'inventory_transfer_manifests.status');
        $this->addFilter('tracking_number', 'inventory_transfer_manifests.tracking_number');
        $this->addFilter('created_at', 'inventory_transfer_manifests.created_at');

        if ($status = request()->query('status')) {
            $queryBuilder->where('inventory_transfer_manifests.status', $status);
        }

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
            'index' => 'manifest_number',
            'label' => trans('inventory::app.admin.datagrid.manifest_number'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-blue-600 dark:text-blue-400">'.$row->manifest_number.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'source_name',
            'label' => trans('inventory::app.admin.datagrid.source'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'destination_name',
            'label' => trans('inventory::app.admin.datagrid.destination'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('inventory::app.admin.datagrid.status'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $badgeClass = match ($row->status) {
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                    'in_transit' => 'bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300',
                    'partially_received' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                    'received' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'discrepancy' => 'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
                    default => 'bg-gray-100 text-gray-800',
                };

                $label = trans("inventory::app.admin.transfer_statuses.{$row->status}") ?: $row->status;

                return '<span class="px-2 py-0.5 rounded text-xs font-semibold '.$badgeClass.'">'.$label.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'total_items_count',
            'label' => trans('inventory::app.admin.datagrid.items_count'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'tracking_number',
            'label' => trans('inventory::app.admin.datagrid.tracking_number'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->tracking_number ?: '<span class="text-gray-400">-</span>';
            },
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
                return route('admin.inventory.transfers.show', $row->id);
            },
        ]);
    }
}
