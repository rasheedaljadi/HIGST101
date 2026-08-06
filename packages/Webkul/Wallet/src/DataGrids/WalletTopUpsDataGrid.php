<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                'wt.status as raw_status',
                'wt.admin_notes',
                'wt.meta',
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
            'index' => 'receipt',
            'label' => 'إشعار التحويل',
            'type' => 'string',
            'closure' => function ($row) {
                $meta = is_string($row->meta) ? json_decode($row->meta, true) : (array) ($row->meta ?? []);
                $receiptPath = $meta['receipt_path'] ?? null;
                if ($receiptPath) {
                    $url = Storage::url($receiptPath);

                    return '<a href="'.$url.'" target="_blank" class="inline-flex items-center gap-1 font-bold text-blue-600 hover:underline bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 px-2.5 py-1 rounded-md text-xs">🖼️ عرض الإشعار</a>';
                }

                return '<span class="text-gray-400">—</span>';
            },
        ]);
        $this->addColumn([
            'index' => 'status',
            'label' => trans('wallet::app.admin.wallet.deposits.status'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                $status = $row->raw_status ?? $row->status;

                $notesHtml = '';
                if (! empty($row->admin_notes)) {
                    $notesHtml = '<div class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400" style="color: #dc2626; font-size: 11px; font-weight: 600; margin-top: 3px; max-width: 150px; word-break: break-word;">السبب: '.e($row->admin_notes).'</div>';
                }

                return match ($status) {
                    'completed', 'approved' => '<span class="badge badge-sm badge-success" style="color: #15803d; background-color: #dcfce7; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">مكتمل</span>',
                    'pending', 'pending_payment', 'payment_received', 'under_review' => '<span class="badge badge-sm badge-warning" style="color: #b45309; background-color: #fef3c7; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">قيد الانتظار</span>',
                    'rejected', 'failed' => '<div><span class="badge badge-sm badge-danger" style="color: #b91c1c; background-color: #fee2e2; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">تم الرفض</span>'.$notesHtml.'</div>',
                    'cancelled' => '<span class="badge badge-sm badge-secondary" style="color: #475569; background-color: #f1f5f9; padding: 4px 10px; border-radius: 9999px; font-weight: bold; font-size: 11px;">ملغي</span>',
                    default => $status,
                };
            },
        ]);
        $this->addColumn(['index' => 'admin_name', 'label' => trans('wallet::app.admin.wallet.deposits.reviewed-by'), 'type' => 'string']);
        $this->addColumn(['index' => 'created_at', 'label' => trans('wallet::app.admin.wallet.deposits.date'), 'type' => 'datetime', 'sortable' => true]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('wallet.deposits.approve')) {
            $this->addAction([
                'index' => 'approve',
                'icon' => 'icon-tick',
                'title' => trans('wallet::app.admin.wallet.deposits.approve'),
                'method' => 'POST',
                'url' => function ($row) {
                    $status = $row->raw_status ?? $row->status;

                    if (! in_array($status, ['pending', 'pending_payment', 'payment_received', 'under_review'])) {
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
                    $status = $row->raw_status ?? $row->status;

                    if (! in_array($status, ['pending', 'pending_payment', 'payment_received', 'under_review'])) {
                        return null;
                    }

                    return route('admin.wallet.deposits.reject', $row->id);
                },
            ]);
        }
    }
}
