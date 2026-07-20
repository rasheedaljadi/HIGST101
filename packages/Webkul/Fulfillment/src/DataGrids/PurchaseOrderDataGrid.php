<?php

namespace Webkul\Fulfillment\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class PurchaseOrderDataGrid extends DataGrid
{
    /**
     * Set primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'purchase_order_id';

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('purchase_orders')
            ->leftJoin('orders', 'purchase_orders.order_id', '=', 'orders.id')
            ->select(
                'purchase_orders.id as purchase_order_id',
                'orders.increment_id as order_increment_id',
                'orders.id as order_id',
                'purchase_orders.provider',
                'purchase_orders.supplier_signature',
                'purchase_orders.supplier_cost',
                'purchase_orders.supplier_currency',
                'purchase_orders.state',
                'purchase_orders.tracking_number',
                'purchase_orders.submitted_at',
                'purchase_orders.internal_reference',
                'purchase_orders.external_order_id',
                'purchase_orders.attempts',
                'purchase_orders.last_error'
            );

        $this->addFilter('purchase_order_id', 'purchase_orders.id');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('provider', 'purchase_orders.provider');
        $this->addFilter('state', 'purchase_orders.state');
        $this->addFilter('tracking_number', 'purchase_orders.tracking_number');
        $this->addFilter('submitted_at', 'purchase_orders.submitted_at');

        $poState = request()->query('po_state');
        if ($poState && $poState !== 'all') {
            if ($poState === 'in_progress') {
                $queryBuilder->whereIn('purchase_orders.state', ['pending', 'submitting']);
            } elseif ($poState === 'completed') {
                $queryBuilder->whereIn('purchase_orders.state', ['shipped', 'delivered']);
            } else {
                $queryBuilder->where('purchase_orders.state', $poState);
            }
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
            'index'      => 'purchase_order_id',
            'label'      => trans('fulfillment::app.admin.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'order_increment_id',
            'label'      => trans('fulfillment::app.admin.datagrid.order-id'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return '<a href="' . route('admin.sales.orders.view', $row->order_id) . '" class="text-blue-600 hover:underline">#' . $row->order_increment_id . '</a>';
            }
        ]);

        $this->addColumn([
            'index'      => 'provider',
            'label'      => trans('fulfillment::app.admin.datagrid.provider'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return '<span class="capitalize">' . htmlspecialchars($row->provider) . '</span>';
            }
        ]);

        $this->addColumn([
            'index'      => 'supplier_signature',
            'label'      => trans('fulfillment::app.admin.datagrid.supplier-store'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'cost',
            'label'      => trans('fulfillment::app.admin.datagrid.cost'),
            'type'       => 'string',
            'sortable'   => true,
            'closure'    => function ($row) {
                if (is_null($row->supplier_cost)) {
                    return 'N/A';
                }
                return core()->formatPrice($row->supplier_cost, $row->supplier_currency ?? 'USD');
            }
        ]);

        $this->addColumn([
            'index'      => 'state',
            'label'      => trans('fulfillment::app.admin.datagrid.state'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $state = $row->state;
                $label = trans("fulfillment::app.admin.states.{$state}");
                
                switch ($state) {
                    case 'pending':
                        return '<span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'submitting':
                        return '<span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-200 dark:border-blue-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'submitted':
                        return '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-400 border border-green-200 dark:border-green-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'shipped':
                        return '<span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-400 border border-purple-200 dark:border-purple-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'delivered':
                        return '<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'needs_manual_review':
                        return '<span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 text-xs font-semibold">' . $label . '</span>';
                    case 'canceled':
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400 border border-gray-200 dark:border-gray-800 text-xs font-semibold">' . $label . '</span>';
                    case 'awaiting_payment_to_supplier':
                        return '<span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 text-xs font-semibold">' . $label . '</span>';
                    default:
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-900 text-xs font-semibold">' . htmlspecialchars($state) . '</span>';
                }
            }
        ]);

        $this->addColumn([
            'index'      => 'tracking_number',
            'label'      => trans('fulfillment::app.admin.datagrid.tracking-number'),
            'type'       => 'string',
            'searchable' => true,
            'closure'    => function ($row) {
                return $row->tracking_number ?: '<span class="text-gray-400 italic">N/A</span>';
            }
        ]);

        $this->addColumn([
            'index'      => 'submitted_at',
            'label'      => trans('fulfillment::app.admin.datagrid.submitted-at'),
            'type'       => 'datetime',
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                return $row->submitted_at ? core()->formatDate($row->submitted_at, 'Y-m-d H:i:s') : '<span class="text-gray-400 italic">N/A</span>';
            }
        ]);

        // Toggleable columns (hidden by default in the grid JSON config)
        $this->addColumn([
            'index'      => 'internal_reference',
            'label'      => trans('fulfillment::app.admin.datagrid.internal-reference'),
            'type'       => 'string',
            'searchable' => true,
            'visibility' => false,
        ]);

        $this->addColumn([
            'index'      => 'external_order_id',
            'label'      => trans('fulfillment::app.admin.datagrid.external-order-id'),
            'type'       => 'string',
            'searchable' => true,
            'visibility' => false,
            'closure'    => function ($row) {
                return $row->external_order_id ?: '<span class="text-gray-400 italic">N/A</span>';
            }
        ]);

        $this->addColumn([
            'index'      => 'attempts',
            'label'      => trans('fulfillment::app.admin.datagrid.attempts'),
            'type'       => 'integer',
            'visibility' => false,
        ]);

        $this->addColumn([
            'index'      => 'last_error',
            'label'      => trans('fulfillment::app.admin.datagrid.last-error'),
            'type'       => 'string',
            'visibility' => false,
            'closure'    => function ($row) {
                return $row->last_error ? htmlspecialchars(substr($row->last_error, 0, 50)) . '...' : '<span class="text-gray-400 italic">None</span>';
            }
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('dropshipping.fulfillment.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('fulfillment::app.admin.datagrid.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.dropshipping.fulfillment.view', $row->purchase_order_id);
                },
            ]);
        }
    }
}
