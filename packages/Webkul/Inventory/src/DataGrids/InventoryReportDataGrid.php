<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryReportDataGrid extends DataGrid
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
        $reportType = request()->query('report_type', 'movements');

        if ($reportType === 'sources') {
            return DB::table('inventory_sources')
                ->leftJoin('product_inventories', 'inventory_sources.id', '=', 'product_inventories.inventory_source_id')
                ->select(
                    'inventory_sources.id as id',
                    'inventory_sources.code as code',
                    'inventory_sources.name as name',
                    'inventory_sources.source_type as source_type',
                    DB::raw('COALESCE(SUM(product_inventories.qty), 0) as total_qty')
                )
                ->groupBy('inventory_sources.id', 'inventory_sources.code', 'inventory_sources.name', 'inventory_sources.source_type');
        }

        // Default: Movements Report
        return DB::table('inventory_movements')
            ->leftJoin('inventory_sources as src', 'inventory_movements.source_inventory_source_id', '=', 'src.id')
            ->leftJoin('inventory_sources as trg', 'inventory_movements.target_inventory_source_id', '=', 'trg.id')
            ->select(
                'inventory_movements.id as id',
                'inventory_movements.movement_type as movement_type',
                'inventory_movements.sku as sku',
                'inventory_movements.quantity as quantity',
                'src.code as source_code',
                'trg.code as target_code',
                'inventory_movements.reference_event as reference_event',
                'inventory_movements.created_at as created_at'
            );
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
            'index' => 'movement_type',
            'label' => trans('inventory::app.admin.datagrid.movement_type'),
            'type' => 'string',
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
            'index' => 'quantity',
            'label' => trans('inventory::app.admin.datagrid.quantity_change'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('inventory::app.admin.datagrid.created_at'),
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
        ]);
    }
}
