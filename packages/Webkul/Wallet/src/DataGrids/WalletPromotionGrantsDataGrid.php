<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletPromotionGrantsDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        return DB::table('wallet_promotion_grants as wpg')
            ->join('wallet_promotions as wp', 'wpg.promotion_id', '=', 'wp.id')
            ->join('customers as c', 'wpg.customer_id', '=', 'c.id')
            ->select(
                'wpg.id',
                'wp.name as promotion_name',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'wpg.original_amount',
                'wpg.remaining_amount',
                'wpg.consumed_amount',
                'wpg.status',
                'wpg.granted_at',
                'wpg.expires_at'
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
            'index' => 'promotion_name',
            'label' => 'العرض',
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
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
            'index' => 'original_amount',
            'label' => 'المبلغ الأصلي',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'remaining_amount',
            'label' => 'المتبقي',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'consumed_amount',
            'label' => 'المستهلك',
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
            'index' => 'granted_at',
            'label' => 'تاريخ المنح',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'expires_at',
            'label' => 'تاريخ الانتهاء',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);
    }
}
