<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletPromotionsDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder()
    {
        return DB::table('wallet_promotions')
            ->select(
                'id',
                'name',
                'type',
                'action_type',
                'reward_value',
                'status',
                'times_used',
                'total_allocated',
                'starts_from',
                'ends_till',
                'created_at'
            );
    }

    /**
     * Add columns.
     */
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
            'index' => 'name',
            'label' => 'اسم العرض',
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'type',
            'label' => 'نوع العرض',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'action_type',
            'label' => 'نوع المكافأة',
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'reward_value',
            'label' => 'القيمة',
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'times_used',
            'label' => 'مرات الاستخدام',
            'type' => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'total_allocated',
            'label' => 'إجمالي الممنوح',
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
            'index' => 'starts_from',
            'label' => 'تاريخ البدء',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'ends_till',
            'label' => 'تاريخ الانتهاء',
            'type' => 'datetime',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions()
    {
        if (function_exists('bouncer') && bouncer()->hasPermission('wallet.promotions.edit')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => 'تعديل',
                'method' => 'GET',
                'url' => fn ($row) => route('admin.wallet.promotions.edit', $row->id),
            ]);
        }

        if (function_exists('bouncer') && bouncer()->hasPermission('wallet.promotions.delete')) {
            $this->addAction([
                'icon' => 'icon-delete',
                'title' => 'أرشفة',
                'method' => 'POST',
                'url' => fn ($row) => route('admin.wallet.promotions.destroy', $row->id),
            ]);
        }
    }
}
