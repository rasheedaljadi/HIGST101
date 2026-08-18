<?php

namespace Webkul\Inventory\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class InventoryMovementDataGrid extends DataGrid
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
        $queryBuilder = DB::table('inventory_movements')
            ->leftJoin('inventory_sources as source_src', 'inventory_movements.source_inventory_source_id', '=', 'source_src.id')
            ->leftJoin('inventory_sources as target_src', 'inventory_movements.target_inventory_source_id', '=', 'target_src.id')
            ->leftJoin('admins', 'inventory_movements.actor_id', '=', 'admins.id')
            ->select(
                'inventory_movements.id as id',
                'inventory_movements.movement_type as movement_type',
                'inventory_movements.product_id as product_id',
                'inventory_movements.sku as sku',
                'inventory_movements.quantity as quantity',
                'source_src.name as source_name',
                'source_src.code as source_code',
                'target_src.name as target_name',
                'target_src.code as target_code',
                'inventory_movements.order_id as order_id',
                'inventory_movements.purchase_order_id as purchase_order_id',
                'inventory_movements.actor_type as actor_type',
                DB::raw("COALESCE(admins.name, inventory_movements.actor_type, 'System') as actor_name"),
                'inventory_movements.reference_event as reference_event',
                'inventory_movements.notes as notes',
                'inventory_movements.created_at as created_at'
            );

        $this->addFilter('id', 'inventory_movements.id');
        $this->addFilter('movement_type', 'inventory_movements.movement_type');
        $this->addFilter('sku', 'inventory_movements.sku');
        $this->addFilter('quantity', 'inventory_movements.quantity');
        $this->addFilter('order_id', 'inventory_movements.order_id');
        $this->addFilter('purchase_order_id', 'inventory_movements.purchase_order_id');
        $this->addFilter('created_at', 'inventory_movements.created_at');

        if ($movementType = request()->query('movement_type')) {
            $queryBuilder->where('inventory_movements.movement_type', $movementType);
        }

        if ($sku = request()->query('sku')) {
            $queryBuilder->where('inventory_movements.sku', 'like', "%{$sku}%");
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
            'index' => 'movement_type',
            'label' => trans('inventory::app.admin.datagrid.movement_type'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $typeClass = match ($row->movement_type) {
                    'hayest_stock_in', 'source_receipt' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'reservation', 'package_prepared' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
                    'handoff_to_delivery_party' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300',
                    'damage_or_loss' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
                    'delivery_failure_return' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                    'quarantine_release' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300',
                    default => 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                };

                $label = trans("inventory::app.admin.movements.{$row->movement_type}") ?: $row->movement_type;

                return '<span class="px-2 py-0.5 rounded text-xs font-semibold '.$typeClass.'">'.$label.'</span>';
            },
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
            'closure' => function ($row) {
                if ($row->quantity > 0) {
                    return '<span class="text-emerald-600 font-bold dark:text-emerald-400">+'.number_format($row->quantity).'</span>';
                } elseif ($row->quantity < 0) {
                    return '<span class="text-rose-600 font-bold dark:text-rose-400">'.number_format($row->quantity).'</span>';
                }

                return '<span class="text-gray-400 font-bold">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'source_name',
            'label' => trans('inventory::app.admin.datagrid.source'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if ($row->source_name && $row->target_name) {
                    return '<span class="text-xs">'.$row->source_name.' → '.$row->target_name.'</span>';
                }

                return $row->target_name ?: $row->source_name ?: '<span class="text-gray-400">-</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'reference',
            'label' => trans('inventory::app.admin.datagrid.reference'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                $refs = [];
                if ($row->order_id) {
                    $refs[] = 'طلب #'.$row->order_id;
                }
                if ($row->purchase_order_id) {
                    $refs[] = 'PO #'.$row->purchase_order_id;
                }
                if ($row->reference_event) {
                    $refs[] = $row->reference_event;
                }

                return ! empty($refs) ? implode(', ', $refs) : '<span class="text-gray-400">-</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'actor_name',
            'label' => trans('inventory::app.admin.datagrid.actor'),
            'type' => 'string',
            'filterable' => false,
            'sortable' => false,
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
}
