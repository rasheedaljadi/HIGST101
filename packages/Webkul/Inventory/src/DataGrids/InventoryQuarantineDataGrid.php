<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryQuarantineDataGrid extends DataGrid
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
        $queryBuilder = DB::table('product_inventories')
            ->join('inventory_sources', 'product_inventories.inventory_source_id', '=', 'inventory_sources.id')
            ->join('products', 'product_inventories.product_id', '=', 'products.id')
            ->whereIn('inventory_sources.code', ['hayest_quarantine_sa', 'hayest_quarantine_ye'])
            ->where('product_inventories.qty', '>', 0)
            ->select(
                'product_inventories.id as id',
                'product_inventories.product_id as product_id',
                'products.sku as sku',
                'inventory_sources.id as inventory_source_id',
                'inventory_sources.name as source_name',
                'inventory_sources.code as source_code',
                'inventory_sources.country as country',
                'product_inventories.qty as quarantine_qty',
                'product_inventories.updated_at as updated_at'
            );

        $this->addFilter('id', 'product_inventories.id');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('source_code', 'inventory_sources.code');
        $this->addFilter('country', 'inventory_sources.country');

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
            'index' => 'sku',
            'label' => trans('inventory::app.admin.datagrid.sku'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-gray-900 dark:text-white">'.$row->sku.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'source_name',
            'label' => trans('inventory::app.admin.datagrid.quarantine_bay'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $badgeClass = $row->source_code === 'hayest_quarantine_sa'
                    ? 'bg-purple-50 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300'
                    : 'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300';

                return '<span class="px-2 py-0.5 rounded text-xs font-semibold '.$badgeClass.'">'.$row->source_name.' ('.$row->country.')</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'quarantine_qty',
            'label' => trans('inventory::app.admin.datagrid.quarantine_qty'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="text-rose-600 font-bold text-base">'.number_format($row->quarantine_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'updated_at',
            'label' => trans('inventory::app.admin.datagrid.last_updated'),
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return core()->formatDate($row->updated_at, 'Y-m-d H:i');
            },
        ]);
    }
}
