<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryCashCollectionDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_cash_collections')
            ->leftJoin('orders', 'delivery_cash_collections.order_id', '=', 'orders.id')
            ->leftJoin('admins as couriers', 'delivery_cash_collections.delivery_boy_id', '=', 'couriers.id')
            ->select(
                'delivery_cash_collections.id',
                'delivery_cash_collections.delivery_assignment_id',
                'orders.increment_id as order_increment_id',
                'delivery_cash_collections.amount',
                'delivery_cash_collections.currency',
                'couriers.name as courier_name',
                'delivery_cash_collections.collected_at',
                'delivery_cash_collections.created_at'
            );

        $this->addFilter('id', 'delivery_cash_collections.id');
        $this->addFilter('order_increment_id', 'orders.increment_id');

        return $queryBuilder;
    }

    public function prepareColumns(): void
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
                return '<a href="'.route('admin.delivery.assignments.show', $row->delivery_assignment_id).'" class="text-blue-600 hover:underline font-bold">#'.$row->order_increment_id.'</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'courier_name',
            'label' => trans('delivery::app.admin.couriers.name'),
            'type' => 'string',
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => trans('delivery::app.admin.settlements.amount-collected'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-emerald-600">'.number_format((float) $row->amount, 2).' '.$row->currency.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'collected_at',
            'label' => 'تاريخ التحصيل',
            'type' => 'datetime',
            'sortable' => true,
            'closure' => function ($row) {
                return $row->collected_at ? core()->formatDate($row->collected_at, 'Y-m-d H:i') : core()->formatDate($row->created_at, 'Y-m-d H:i');
            },
        ]);
    }
}
