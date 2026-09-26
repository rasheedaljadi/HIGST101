<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\DataGrid\DataGrid;

class InventorySourceItemsDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'inventory_id';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $currentLocale = app()->getLocale();
        $sourceId = request()->route('id');

        $queryBuilder = DB::table('product_inventories as pi')
            ->join('products as p', 'pi.product_id', '=', 'p.id')
            ->leftJoin('product_flat as pf', function ($join) use ($currentLocale) {
                $join->on('p.id', '=', 'pf.product_id')
                    ->where('pf.locale', '=', $currentLocale);
            })
            ->leftJoin('products as parent_p', 'p.parent_id', '=', 'parent_p.id')
            ->leftJoin('product_flat as parent_pf', function ($join) use ($currentLocale) {
                $join->on('parent_p.id', '=', 'parent_pf.product_id')
                    ->where('parent_pf.locale', '=', $currentLocale);
            })
            ->where('pi.inventory_source_id', $sourceId)
            ->select(
                'pi.id as inventory_id',
                'pi.qty as qty',
                'p.id as product_id',
                'p.sku as sku',
                'p.type as product_type',
                'p.parent_id as parent_id',
                DB::raw('COALESCE(pf.name, parent_pf.name, p.sku) as product_name'),
                DB::raw('COALESCE(pf.price, parent_pf.price, 0) as price'),
                DB::raw('COALESCE(pf.status, parent_pf.status, 0) as status')
            );

        $this->addFilter('inventory_id', 'pi.id');
        $this->addFilter('product_id', 'p.id');
        $this->addFilter('sku', 'p.sku');
        $this->addFilter('product_name', 'pf.name');
        $this->addFilter('product_type', 'p.type');
        $this->addFilter('status', 'pf.status');
        $this->addFilter('qty', 'pi.qty');

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
            'index'      => 'product_id',
            'label'      => trans('inventory::app.admin.datagrid.id') ?: 'المعرف',
            'type'       => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('inventory::app.admin.datagrid.sku') ?: 'رمز SKU',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return '<span class="font-mono font-semibold text-xs">' . e($row->sku) . '</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'product_name',
            'label'      => trans('inventory::app.admin.datagrid.product_name') ?: 'اسم المنتج',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $shortName = Str::limit($row->product_name, 45, '...');
                $targetId = $row->parent_id ?: $row->product_id;
                $url = route('admin.inventory.products.show', $targetId);

                return '<a href="' . $url . '" class="text-blue-600 dark:text-blue-400 font-medium hover:underline" title="' . e($row->product_name) . '">' . e($shortName) . '</a>';
            },
        ]);

        $this->addColumn([
            'index'      => 'product_type',
            'label'      => trans('admin::app.catalog.products.index.datagrid.type') ?: 'النوع',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return '<span class="text-xs uppercase font-medium text-gray-600 dark:text-gray-400">' . e($row->product_type) . '</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'qty',
            'label'      => 'الكمية في المخزن',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if ($row->qty <= 0) {
                    return '<span class="badge badge-md badge-danger font-bold">0 (منتهي)</span>';
                }

                if ($row->qty <= 5) {
                    return '<span class="badge badge-md badge-warning font-bold">' . number_format($row->qty) . ' (منخفض)</span>';
                }

                return '<span class="badge badge-md badge-success font-bold">' . number_format($row->qty) . '</span>';
            },
        ]);


        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('inventory::app.admin.datagrid.status') ?: 'الحالة',
            'type'       => 'boolean',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return $row->status
                    ? '<span class="badge badge-md badge-success">' . (trans('inventory::app.admin.datagrid.active') ?: 'نشط') . '</span>'
                    : '<span class="badge badge-md badge-danger">' . (trans('inventory::app.admin.datagrid.inactive') ?: 'معطل') . '</span>';
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
            'icon'   => 'icon-view',
            'title'  => 'معاينة بطاقة المنتج والمخزون',
            'method' => 'GET',
            'url'    => function ($row) {
                $targetId = $row->parent_id ?: $row->product_id;

                return route('admin.inventory.products.show', $targetId);
            },
        ]);

        if (bouncer()->hasPermission('catalog.products.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => 'تعديل المنتج في الكتالوج',
                'method' => 'GET',
                'url'    => function ($row) {
                    $targetId = $row->parent_id ?: $row->product_id;

                    return route('admin.catalog.products.edit', $targetId);
                },
            ]);
        }
    }
}
