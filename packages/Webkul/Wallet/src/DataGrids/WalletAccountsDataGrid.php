<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletAccountsDataGrid extends DataGrid
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
        return DB::table('wallet_accounts as wa')
            ->join('customers as c', 'wa.customer_id', '=', 'c.id')
            ->select(
                'wa.id',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'c.email',
                'wa.available_balance',
                'wa.held_balance',
                'wa.total_balance',
                'wa.currency_code',
                'wa.status',
                'wa.created_at'
            );
    }

    /**
     * Add columns.
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('wallet::app.admin.wallet.accounts.id'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_name',
            'label' => trans('wallet::app.admin.wallet.accounts.customer'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'email',
            'label' => trans('wallet::app.admin.wallet.accounts.email'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable' => false,
        ]);

        $this->addColumn([
            'index' => 'available_balance',
            'label' => trans('wallet::app.admin.wallet.accounts.available-balance'),
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->available_balance),
        ]);

        $this->addColumn([
            'index' => 'held_balance',
            'label' => trans('wallet::app.admin.wallet.accounts.held-balance'),
            'type' => 'decimal',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->held_balance),
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('wallet::app.admin.wallet.accounts.status'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'options' => [
                'type' => 'basic',
                'params' => [
                    'options' => [
                        ['label' => 'Active',    'value' => 'active'],
                        ['label' => 'Suspended', 'value' => 'suspended'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Add actions.
     */
    public function prepareActions()
    {
        $this->addAction([
            'index' => 'show',
            'icon' => 'icon-view',
            'title' => trans('wallet::app.admin.wallet.accounts.view') ?? 'عرض التفاصيل',
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.wallet.accounts.show', $row->id);
            },
        ]);
    }
}
