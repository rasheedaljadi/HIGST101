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
                'orders.grand_total as cod_amount',
                'orders.order_currency_code as currency_code',
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
            'closure' => function ($row) {
                return '<a href="'.route('admin.delivery.assignments.show', $row->id).'" class="text-blue-600 hover:underline font-bold">#'.$row->order_increment_id.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'customer_name',
            'label' => trans('delivery::app.admin.datagrid.customer'),
            'type' => 'string',
            'closure' => function ($row) {
                return '<div><div class="font-medium text-gray-800 dark:text-white">'.$row->customer_first_name.' '.$row->customer_last_name.'</div><div class="text-xs text-gray-500">'.$row->customer_phone.'</div></div>';
            },
        ]);

        $this->addColumn([
            'index' => 'governorate',
            'label' => trans('delivery::app.admin.datagrid.governorate'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-semibold text-gray-800 dark:text-white">'.$row->governorate.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'delivery_type',
            'label' => trans('delivery::app.admin.datagrid.type'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->delivery_type === 'home_delivery'
                    ? '<span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 text-xs font-semibold">🏠 منزلي</span>'
                    : '<span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 text-xs font-semibold">📍 نقطة استلام</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'payment_method',
            'label' => trans('delivery::app.admin.datagrid.payment-method'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                if ($row->payment_method === 'cashondelivery') {
                    return '<span class="px-2 py-0.5 rounded text-xs bg-emerald-100 text-emerald-800 font-semibold">💵 عند الاستلام (COD)</span>';
                }

                return '<span class="px-2 py-0.5 rounded text-xs bg-sky-100 text-sky-800 font-semibold">💳 مسبق الدفع</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'agent_info',
            'label' => trans('delivery::app.admin.datagrid.assigned-to'),
            'type' => 'string',
            'closure' => function ($row) {
                if ($row->delivery_type === 'home_delivery') {
                    return $row->courier_name
                        ? '<span class="font-medium text-indigo-700 dark:text-indigo-400">🚴 '.$row->courier_name.'</span>'
                        : '<span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">بانتظار مندوب</span>';
                }

                return $row->point_name
                    ? '<span class="font-medium text-purple-700 dark:text-purple-400">🏢 '.$row->point_name.'</span>'
                    : '<span class="text-xs text-gray-400">غير محدد</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('delivery::app.admin.datagrid.status'),
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                $status = (string) $row->status;
                $label = trans("delivery::app.admin.assignments.status.{$status}");

                switch ($status) {
                    case 'ready_for_assignment':
                        return '<span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700 text-xs font-semibold">'.$label.'</span>';
                    case 'assigned':
                        return '<span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-xs font-semibold">'.$label.'</span>';
                    case 'picked_up':
                        return '<span class="px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-300 dark:border-indigo-700 text-xs font-semibold">'.$label.'</span>';
                    case 'out_for_delivery':
                        return '<span class="px-2.5 py-1 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-300 dark:border-purple-700 text-xs font-semibold animate-pulse">'.$label.'</span>';
                    case 'arrived_at_point':
                        return '<span class="px-2.5 py-1 rounded-full bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-700 text-xs font-semibold">'.$label.'</span>';
                    case 'delivered':
                        return '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 border border-green-300 dark:border-green-700 text-xs font-semibold">✓ '.$label.'</span>';
                    case 'delivery_failed':
                        return '<span class="px-2.5 py-1 rounded-full bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border border-red-300 dark:border-red-700 text-xs font-semibold">✗ '.$label.' ('.$row->attempt_count.')</span>';
                    case 'retry_scheduled':
                        return '<span class="px-2.5 py-1 rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 border border-orange-300 dark:border-orange-700 text-xs font-semibold">🔄 '.$label.'</span>';
                    case 'returned_to_hayest':
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border border-gray-300 dark:border-gray-700 text-xs font-semibold">'.$label.'</span>';
                    default:
                        return '<span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-800 text-xs font-semibold">'.htmlspecialchars($status).'</span>';
                }
            },
        ]);

        $this->addColumn([
            'index' => 'cod_amount',
            'label' => trans('delivery::app.admin.datagrid.cod-amount'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                if (strtolower((string) $row->payment_method) !== 'cashondelivery' || ! $row->cod_amount || (float) $row->cod_amount <= 0) {
                    return '<span class="text-gray-400">-</span>';
                }

                $currency = $row->currency_code ?: core()->getBaseCurrencyCode();

                return '<span class="font-bold text-emerald-600">'.core()->formatPrice((float) $row->cod_amount, $currency).'</span>';
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
            'icon' => 'icon-view',
            'title' => trans('delivery::app.admin.datagrid.view'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.delivery.assignments.show', $row->id);
            },
        ]);
    }
}
