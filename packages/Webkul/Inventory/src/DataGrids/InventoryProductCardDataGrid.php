<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryProductCardDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'product_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $currentLocale = app()->getLocale();

        $queryBuilder = DB::table('products')
            ->leftJoin('product_flat', function ($join) use ($currentLocale) {
                $join->on('products.id', '=', 'product_flat.product_id')
                    ->where('product_flat.locale', '=', $currentLocale);
            })
            ->whereNull('products.parent_id')
            ->select(
                'products.id as product_id',
                'products.sku as sku',
                'products.type as product_type',
                DB::raw('COALESCE(product_flat.name, products.sku) as product_name'),
                // Salable Yemen Stock (hayest_internal_ye + hayest_dropship_ye)
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code IN ('hayest_internal_ye', 'hayest_dropship_ye')) as salable_ye_qty"),
                // Internal Ready Stock
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code = 'hayest_internal_ye') as internal_ye_qty"),
                // Dropship Transit Hub Stock
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code = 'hayest_dropship_ye') as dropship_ye_qty"),
                // Saudi Staging Hub
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code = 'hayest_dropship_sa') as staging_sa_qty"),
                // Quarantine (SA + YE)
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code IN ('hayest_quarantine_sa', 'hayest_quarantine_ye')) as quarantine_qty"),
                // Virtual Projection (AliExpress - isolated)
                DB::raw("(SELECT COALESCE(SUM(pi.qty), 0) FROM product_inventories pi 
                          JOIN inventory_sources src ON pi.inventory_source_id = src.id 
                          WHERE (pi.product_id = products.id OR pi.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND src.code = 'aliexpress_source') as virtual_projection_qty"),
                // Allocated for active orders
                DB::raw("(SELECT COALESCE(SUM(oa.reserved_qty), 0) FROM order_allocations oa 
                          WHERE (oa.product_id = products.id OR oa.product_id IN (SELECT child_p.id FROM products child_p WHERE child_p.parent_id = products.id))
                          AND oa.state = 'reserved') as allocated_qty")
            )
            ->groupBy('products.id', 'products.sku', 'products.type', 'product_flat.name');

        $this->addFilter('product_id', 'products.id');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('product_name', 'product_flat.name');
        $this->addFilter('product_type', 'products.type');

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
            'index' => 'product_id',
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
        ]);

        $this->addColumn([
            'index' => 'product_name',
            'label' => trans('inventory::app.admin.datagrid.product_name'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'salable_ye_qty',
            'label' => trans('inventory::app.admin.datagrid.salable_ye_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-emerald-600 dark:text-emerald-400">'.number_format($row->salable_ye_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'internal_ye_qty',
            'label' => trans('inventory::app.admin.datagrid.internal_ye_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="text-blue-600 dark:text-blue-400">'.number_format($row->internal_ye_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'dropship_ye_qty',
            'label' => trans('inventory::app.admin.datagrid.dropship_ye_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="text-indigo-600 dark:text-indigo-400">'.number_format($row->dropship_ye_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'staging_sa_qty',
            'label' => trans('inventory::app.admin.datagrid.staging_sa_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="text-gray-600 dark:text-gray-400">'.number_format($row->staging_sa_qty).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'quarantine_qty',
            'label' => trans('inventory::app.admin.datagrid.quarantine_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->quarantine_qty > 0
                    ? '<span class="text-rose-600 dark:text-rose-400 font-semibold">'.number_format($row->quarantine_qty).'</span>'
                    : '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'allocated_qty',
            'label' => trans('inventory::app.admin.datagrid.allocated_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->allocated_qty > 0
                    ? '<span class="text-amber-600 dark:text-amber-400 font-semibold">'.number_format($row->allocated_qty).'</span>'
                    : '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'virtual_projection_qty',
            'label' => trans('inventory::app.admin.datagrid.virtual_projection_qty'),
            'type' => 'integer',
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">'.number_format($row->virtual_projection_qty).'</span>';
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
                return route('admin.inventory.products.show', $row->product_id);
            },
        ]);
    }
}
