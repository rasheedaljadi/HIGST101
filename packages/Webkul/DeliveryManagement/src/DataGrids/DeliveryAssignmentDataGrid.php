<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryAssignmentDataGrid extends DataGrid
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
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('delivery_assignments')
            ->leftJoin('orders', 'delivery_assignments.order_id', '=', 'orders.id')
            ->leftJoin('addresses as order_address', function ($join) {
                $join->on('orders.id', '=', 'order_address.order_id')
                    ->where('order_address.address_type', '=', 'order_shipping');
            })
            ->leftJoin('admins as couriers', 'delivery_assignments.delivery_boy_id', '=', 'couriers.id')
            ->leftJoin('delivery_points', 'delivery_assignments.delivery_point_id', '=', 'delivery_points.id')
            ->select(
                'delivery_assignments.id as id',
                'delivery_assignments.order_id',
                'orders.increment_id as order_increment_id',
                'orders.customer_first_name',
                'orders.customer_last_name',
                'order_address.phone as customer_phone',
                'delivery_assignments.state_code as governorate',
                'delivery_assignments.delivery_type',
                'delivery_assignments.payment_method',
                'delivery_assignments.status',
                'delivery_assignments.attempt_count',
                'delivery_assignments.cod_amount_yer',
                'couriers.name as courier_name',
                'delivery_points.name as point_name',
                'delivery_assignments.created_at',
                'delivery_assignments.updated_at'
            );

        $this->addFilter('id', 'delivery_assignments.id');
        $this->addFilter('order_increment_id', 'orders.increment_id');
        $this->addFilter('status', 'delivery_assignments.status');
        $this->addFilter('delivery_type', 'delivery_assignments.delivery_type');
        $this->addFilter('payment_method', 'delivery_assignments.payment_method');
        $this->addFilter('governorate', 'delivery_assignments.state_code');

        if ($status = request()->query('status')) {
            $queryBuilder->where('delivery_assignments.status', $status);
        }

        if ($deliveryType = request()->query('delivery_type')) {
            $queryBuilder->where('delivery_assignments.delivery_type', $deliveryType);
        }

        if ($courierId = request()->query('delivery_boy_id')) {
            $queryBuilder->where('delivery_assignments.delivery_boy_id', $courierId);
        }

        if ($pointId = request()->query('delivery_point_id')) {
            $queryBuilder->where('delivery_assignments.delivery_point_id', $pointId);
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
            'label' => trans('delivery::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_increment_id',
            'label' => trans('delivery::app.admin.datagrid.order-id'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<a href="'.route('admin.sales.orders.view', $row->order_id).'" class="text-blue-600 hover:underline font-semibold">#'.$row->order_increment_id.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'customer',
            'label' => trans('delivery::app.admin.datagrid.customer'),
            'type' => 'string',
            'closure' => function ($row) {
                $name = trim(($row->customer_first_name ?? '').' '.($row->customer_last_name ?? ''));
                $phone = $row->customer_phone ?? '';

                return '<div><div class="font-medium">'.($name ?: 'N/A').'</div><div class="text-xs text-gray-500">'.$phone.'</div></div>';
            },
        ]);

        $this->addColumn([
            'index' => 'governorate',
            'label' => trans('delivery::app.admin.datagrid.governorate'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'delivery_type',
            'label' => trans('delivery::app.admin.datagrid.type'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                return $row->delivery_type === 'home_delivery'
                    ? '<span class="px-2 py-0.5 rounded text-xs bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 font-medium">'.trans('delivery::app.admin.datagrid.home-delivery').'</span>'
                    : '<span class="px-2 py-0.5 rounded text-xs bg-cyan-50 text-cyan-700 dark:bg-cyan-950/50 dark:text-cyan-300 font-medium">'.trans('delivery::app.admin.datagrid.delivery-point').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'payment_method',
            'label' => trans('delivery::app.admin.datagrid.payment'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                $isCod = str_contains($row->payment_method ?? '', 'cod') || $row->payment_method === 'cashondelivery';

                return $isCod
                    ? '<span class="px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-400 font-medium">'.trans('delivery::app.admin.datagrid.cod').'</span>'
                    : '<span class="px-2 py-0.5 rounded text-xs bg-green-50 text-green-800 dark:bg-green-950/50 dark:text-green-400 font-medium">'.trans('delivery::app.admin.datagrid.prepaid').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'agent',
            'label' => trans('delivery::app.admin.datagrid.agent'),
            'type' => 'string',
            'closure' => function ($row) {
                if ($row->delivery_type === 'home_delivery') {
                    return $row->courier_name
                        ? '<span class="text-xs font-semibold text-gray-800 dark:text-gray-200">'.$row->courier_name.'</span>'
                        : '<span class="text-xs text-rose-500 italic">غير مسند</span>';
                }

                return $row->point_name
                    ? '<span class="text-xs font-semibold text-gray-800 dark:text-gray-200">'.$row->point_name.'</span>'
                    : '<span class="text-xs text-rose-500 italic">نقطة غير محددة</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('delivery::app.admin.datagrid.status'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $status = $row->status;
                $label = trans("delivery::app.admin.states.{$status}");

                switch ($status) {
                    case 'ready_for_assignment':
                        return '<span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-900 text-xs font-semibold">'.$label.'</span>';
                    case 'assigned':
                        return '<span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-200 dark:border-blue-900 text-xs font-semibold">'.$label.'</span>';
                    case 'picked_up':
                        return '<span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-400 border border-purple-200 dark:border-purple-900 text-xs font-semibold">'.$label.'</span>';
                    case 'out_for_delivery':
                        return '<span class="px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900 text-xs font-semibold">'.$label.'</span>';
                    case 'arrived_at_point':
                        return '<span class="px-2.5 py-1 rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-900 text-xs font-semibold">'.$label.'</span>';
                    case 'delivered':
                        return '<span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900 text-xs font-semibold">'.$label.'</span>';
                    case 'delivery_failed':
                        return '<span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-400 border border-rose-200 dark:border-rose-900 text-xs font-semibold">'.$label.'</span>';
                    case 'retry_scheduled':
                        return '<span class="px-2.5 py-1 rounded-full bg-orange-100 text-orange-800 dark:bg-orange-950/50 dark:text-orange-400 border border-orange-200 dark:border-orange-900 text-xs font-semibold">'.$label.'</span>';
                    case 'returned_to_hayest':
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-700 text-xs font-semibold">'.$label.'</span>';
                    default:
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 text-xs font-semibold">'.htmlspecialchars($status).'</span>';
                }
            },
        ]);

        $this->addColumn([
            'index' => 'cod_amount_yer',
            'label' => trans('delivery::app.admin.datagrid.cod-amount'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                if (! $row->cod_amount_yer || $row->cod_amount_yer <= 0) {
                    return '<span class="text-gray-400">-</span>';
                }

                return '<span class="font-bold text-emerald-600">'.number_format($row->cod_amount_yer, 2).' YER</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('delivery::app.admin.datagrid.created-at'),
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
            'icon' => 'icon-eye',
            'title' => trans('delivery::app.admin.datagrid.view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.delivery.assignments.show', $row->id);
            },
        ]);
    }
}
