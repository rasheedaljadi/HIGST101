<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliverySettlementDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_settlements')
            ->leftJoin('admins as couriers', 'delivery_settlements.delivery_boy_id', '=', 'couriers.id')
            ->leftJoin('admins as supervisors', 'delivery_settlements.settled_by', '=', 'supervisors.id')
            ->select(
                'delivery_settlements.id',
                'couriers.name as courier_name',
                'delivery_settlements.settlement_date',
                'delivery_settlements.expected_amount',
                'delivery_settlements.actual_amount',
                'delivery_settlements.difference',
                'delivery_settlements.currency',
                'delivery_settlements.status',
                'supervisors.name as supervisor_name',
                'delivery_settlements.created_at'
            );

        $this->addFilter('id', 'delivery_settlements.id');
        $this->addFilter('status', 'delivery_settlements.status');

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
            'index' => 'courier_name',
            'label' => trans('delivery::app.admin.couriers.name'),
            'type' => 'string',
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'settlement_date',
            'label' => 'تاريخ التسوية',
            'type' => 'date',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'expected_amount',
            'label' => trans('delivery::app.admin.settlements.amount-expected'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                $currency = $row->currency ?: core()->getBaseCurrencyCode();

                return '<span class="font-bold text-gray-800">'.core()->formatPrice((float) $row->expected_amount, $currency).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'actual_amount',
            'label' => trans('delivery::app.admin.settlements.amount-submitted'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                $currency = $row->currency ?: core()->getBaseCurrencyCode();

                return '<span class="font-bold text-emerald-600">'.core()->formatPrice((float) $row->actual_amount, $currency).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'difference',
            'label' => trans('delivery::app.admin.settlements.discrepancy'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                $diff = (float) $row->difference;
                $currency = $row->currency ?: core()->getBaseCurrencyCode();

                if ($diff == 0) {
                    return '<span class="text-xs text-emerald-600 font-bold">0.00 (مطابق)</span>';
                }

                return '<span class="text-xs text-rose-600 font-bold">'.core()->formatPrice($diff, $currency).' (عجز/فارق)</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('delivery::app.admin.datagrid.status'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                return $row->status === 'settled'
                    ? '<span class="px-2.5 py-1 rounded-full bg-green-100 text-green-800 text-xs font-semibold">معتمدة</span>'
                    : '<span class="px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-semibold">قيد المراجعة</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'supervisor_name',
            'label' => trans('delivery::app.admin.settlements.settled-by'),
            'type' => 'string',
        ]);
    }
}
