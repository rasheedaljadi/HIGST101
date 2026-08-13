<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletPromotionUsagesDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        return DB::table('wallet_promotion_usages as wpu')
            ->join('wallet_promotions as wp', 'wpu.promotion_id', '=', 'wp.id')
            ->join('customers as c', 'wpu.customer_id', '=', 'c.id')
            ->select(
                'wpu.id',
                'wp.name as promotion_name',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'wpu.event_key',
                'wpu.reward_amount',
                'wpu.net_credited_amount',
                'wpu.status',
                'wpu.created_at'
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
            'index' => 'event_key',
            'label' => 'مفتاح الحدث',
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ]);

        $this->addColumn([
            'index' => 'reward_amount',
            'label' => 'قيمة المكافأة',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'net_credited_amount',
            'label' => 'الصافي المضاف',
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
            'index' => 'created_at',
            'label' => 'التاريخ',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);
    }
}
