<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletTopUpsDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        return DB::table('wallet_topups as wt')
            ->join('wallet_accounts as wa', 'wt.wallet_id', '=', 'wa.id')
            ->join('customers as c', 'wa.customer_id', '=', 'c.id')
            ->leftJoin('admins as a', 'wt.admin_user_id', '=', 'a.id')
            ->select(
                'wt.id',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'wt.amount',
                'wt.currency_code',
                'wt.payment_method',
                'wt.status',
                DB::raw("COALESCE(a.name, '—') as admin_name"),
                'wt.approved_at',
                'wt.created_at'
            );
    }

    public function prepareColumns()
    {
        $this->addColumn(['index' => 'id', 'label' => '#', 'type' => 'integer', 'sortable' => true]);
        $this->addColumn(['index' => 'customer_name', 'label' => trans('wallet::app.admin.wallet.deposits.customer'), 'type' => 'string', 'searchable' => true]);
        $this->addColumn([
            'index' => 'amount',
            'label' => trans('wallet::app.admin.wallet.deposits.amount'),
            'type' => 'decimal',
            'sortable' => true,
            'closure' => fn ($row) => core()->formatBasePrice($row->amount),
        ]);
        $this->addColumn(['index' => 'payment_method', 'label' => trans('wallet::app.admin.wallet.deposits.payment-method'), 'type' => 'string']);
        $this->addColumn([
            'index' => 'status',
            'label' => trans('wallet::app.admin.wallet.deposits.status'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                return match ($row->status) {
                    'completed', 'approved' => '<span class="badge badge-sm badge-success" style="color: #15803d; background-color: #dcfce7; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">مكتمل</span>',
                    'pending', 'pending_payment', 'payment_received', 'under_review' => '<span class="badge badge-sm badge-warning" style="color: #b45309; background-color: #fef3c7; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">قيد الانتظار</span>',
                    'rejected' => '<span class="badge badge-sm badge-danger" style="color: #b91c1c; background-color: #fee2e2; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">مرفوض</span>',
                    'failed' => '<span class="badge badge-sm badge-danger" style="color: #b91c1c; background-color: #fee2e2; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">فشلت</span>',
                    'cancelled' => '<span class="badge badge-sm badge-secondary" style="color: #475569; background-color: #f1f5f9; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">ملغي</span>',
                    default => $row->status,
                };
            },
        ]);
        $this->addColumn(['index' => 'admin_name', 'label' => trans('wallet::app.admin.wallet.deposits.reviewed-by'), 'type' => 'string']);
        $this->addColumn(['index' => 'created_at', 'label' => trans('wallet::app.admin.wallet.deposits.date'), 'type' => 'datetime', 'sortable' => true]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'index' => 'approve',
            'icon' => 'icon-tick',
            'title' => trans('wallet::app.admin.wallet.deposits.approve'),
            'method' => 'POST',
            'url' => function ($row) {
                if (! in_array($row->status, ['pending_payment', 'payment_received', 'under_review'])) {
                    return null;
                }

                return route('admin.wallet.deposits.approve', $row->id);
            },
        ]);

        $this->addAction([
            'index' => 'reject',
            'icon' => 'icon-cross',
            'title' => trans('wallet::app.admin.wallet.deposits.reject'),
            'method' => 'POST',
            'url' => function ($row) {
                if (! in_array($row->status, ['pending_payment', 'payment_received', 'under_review'])) {
                    return null;
                }

                return route('admin.wallet.deposits.reject', $row->id);
            },
        ]);
    }
}
