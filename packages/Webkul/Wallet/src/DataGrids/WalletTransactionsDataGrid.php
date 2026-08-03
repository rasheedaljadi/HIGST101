<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletTransactionsDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    public function __construct(protected int $walletId = 0)
    {
        parent::__construct();
    }

    public function prepareQueryBuilder()
    {
        $query = DB::table('wallet_transactions as wt')
            ->select('wt.id', 'wt.type', 'wt.direction', 'wt.amount', 'wt.running_balance', 'wt.description', 'wt.created_at');

        if ($this->walletId) {
            $query->where('wt.wallet_id', $this->walletId);
        }

        return $query;
    }

    public function prepareColumns()
    {
        $this->addColumn(['index' => 'id', 'label' => '#', 'type' => 'integer', 'sortable' => true]);
        $this->addColumn(['index' => 'type', 'label' => trans('wallet::app.admin.wallet.transactions.type'), 'type' => 'string', 'filterable' => true]);
        $this->addColumn(['index' => 'direction', 'label' => trans('wallet::app.admin.wallet.transactions.direction'), 'type' => 'string']);
        $this->addColumn([
            'index' => 'amount',
            'label' => trans('wallet::app.admin.wallet.transactions.amount'),
            'type' => 'decimal',
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->amount),
        ]);
        $this->addColumn([
            'index' => 'running_balance',
            'label' => trans('wallet::app.admin.wallet.transactions.balance-after'),
            'type' => 'decimal',
            'closure' => fn ($row) => core()->formatBasePrice($row->running_balance),
        ]);
        $this->addColumn(['index' => 'description', 'label' => trans('wallet::app.admin.wallet.transactions.description'), 'type' => 'string']);
        $this->addColumn(['index' => 'created_at', 'label' => trans('wallet::app.admin.wallet.transactions.date'), 'type' => 'datetime', 'sortable' => true]);
    }
}
