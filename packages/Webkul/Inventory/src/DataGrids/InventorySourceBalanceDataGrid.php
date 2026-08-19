<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventorySourceBalanceDataGrid extends DataGrid
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
        $queryBuilder = DB::table('inventory_sources')
            ->leftJoin('product_inventories', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
            ->select(
                'inventory_sources.id as id',
                'inventory_sources.code as code',
                'inventory_sources.name as name',
                'inventory_sources.country as country',
                'inventory_sources.city as city',
                'inventory_sources.source_type as source_type',
                'inventory_sources.is_salable as is_salable',
                'inventory_sources.is_delivery_source as is_delivery_source',
                'inventory_sources.status as status',
                DB::raw('COALESCE(SUM(product_inventories.qty), 0) as available_qty')
            )
            ->groupBy(
                'inventory_sources.id',
                'inventory_sources.code',
                'inventory_sources.name',
                'inventory_sources.country',
                'inventory_sources.city',
                'inventory_sources.source_type',
                'inventory_sources.is_salable',
                'inventory_sources.is_delivery_source',
                'inventory_sources.status'
            );

        $this->addFilter('id', 'inventory_sources.id');
        $this->addFilter('code', 'inventory_sources.code');
        $this->addFilter('name', 'inventory_sources.name');
        $this->addFilter('country', 'inventory_sources.country');
        $this->addFilter('city', 'inventory_sources.city');
        $this->addFilter('source_type', 'inventory_sources.source_type');
        $this->addFilter('is_salable', 'inventory_sources.is_salable');
        $this->addFilter('is_delivery_source', 'inventory_sources.is_delivery_source');
        $this->addFilter('status', 'inventory_sources.status');

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
            'index' => 'code',
            'label' => trans('inventory::app.admin.datagrid.code'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('inventory::app.admin.datagrid.name'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'location',
            'label' => trans('inventory::app.admin.datagrid.location'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                return ($row->city ? $row->city.', ' : '').$row->country;
            },
        ]);

        $this->addColumn([
            'index' => 'source_type',
            'label' => trans('inventory::app.admin.datagrid.source_type'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return trans("inventory::app.admin.source_types.{$row->source_type}") ?: $row->source_type;
            },
        ]);

        $this->addColumn([
            'index' => 'is_salable',
            'label' => trans('inventory::app.admin.datagrid.is_salable'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->is_salable
                    ? '<span class="badge badge-md badge-success">'.trans('inventory::app.admin.datagrid.yes').'</span>'
                    : '<span class="badge badge-md badge-danger">'.trans('inventory::app.admin.datagrid.no').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'is_delivery_source',
            'label' => trans('inventory::app.admin.datagrid.is_delivery_source'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->is_delivery_source
                    ? '<span class="badge badge-md badge-success">'.trans('inventory::app.admin.datagrid.yes').'</span>'
                    : '<span class="badge badge-md badge-danger">'.trans('inventory::app.admin.datagrid.no').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'available_qty',
            'label' => trans('inventory::app.admin.datagrid.available_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->code === 'default') {
                    return '<span class="text-slate-500 font-semibold">'.number_format($row->available_qty).' <small class="text-xs text-slate-400">(Legacy / External)</small></span>';
                }

                if ($row->code === 'aliexpress_source') {
                    return '<span class="text-amber-600 font-bold">'.number_format($row->available_qty).' ('.trans('inventory::app.admin.datagrid.virtual_projection').')</span>';
                }

                return '<span class="font-bold text-gray-900 dark:text-white">'.number_format($row->available_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('inventory::app.admin.datagrid.status'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->status
                    ? '<span class="badge badge-md badge-success">'.trans('inventory::app.admin.datagrid.active').'</span>'
                    : '<span class="badge badge-md badge-danger">'.trans('inventory::app.admin.datagrid.inactive').'</span>';
            },
        ]);
    }
}
