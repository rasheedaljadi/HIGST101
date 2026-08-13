<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletPromoDebtsDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        return DB::table('wallet_promo_debts as wpd')
            ->join('customers as c', 'wpd.customer_id', '=', 'c.id')
            ->select(
                'wpd.id',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'wpd.order_id',
                'wpd.original_debt_amount',
                'wpd.remaining_debt_amount',
                'wpd.settled_amount',
                'wpd.status',
                'wpd.reason',
                'wpd.created_at'
            );
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => 'ID',
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_name',
            'label' => 'العميل',
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'order_id',
            'label' => 'رقم الطلب',
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'original_debt_amount',
            'label' => 'أصل الدين',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'remaining_debt_amount',
            'label' => 'المتبقي',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'settled_amount',
            'label' => 'المسدد',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'الحالة',
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'reason',
            'label' => 'السبب',
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => 'تاريخ الإنشاء',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);
    }
}
