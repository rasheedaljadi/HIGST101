<?php

namespace Webkul\Wallet\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class WalletWithdrawalsDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        return DB::table('wallet_withdrawal_requests as wr')
            ->join('wallet_accounts as wa', 'wr.wallet_id', '=', 'wa.id')
            ->join('customers as c', 'wa.customer_id', '=', 'c.id')
            ->leftJoin('admins as a', 'wr.admin_user_id', '=', 'a.id')
            ->select(
                'wr.id',
                DB::raw("CONCAT(c.first_name, ' ', c.last_name) as customer_name"),
                'c.email as customer_email',
                'wr.amount',
                'wr.currency_code',
                'wr.status',
                'wr.status as raw_status',
                'wr.bank_details',
                'wr.rejection_reason',
                DB::raw("COALESCE(a.name, '—') as admin_name"),
                'wr.transferred_at',
                'wr.created_at'
            );
    }

    public function prepareColumns()
    {
        $parseBankDetails = function ($row) {
            if (empty($row->bank_details)) {
                return [];
            }

            if (is_array($row->bank_details)) {
                return $row->bank_details;
            }

            try {
                $decrypted = decrypt($row->bank_details);
                if (is_array($decrypted)) {
                    return $decrypted;
                }
                $json = json_decode($decrypted, true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (\Throwable $e) {
                // Fallback for raw json string
            }

            $json = json_decode($row->bank_details, true);

            return is_array($json) ? $json : [];
        };

        $this->addColumn(['index' => 'id', 'label' => '#', 'type' => 'integer', 'sortable' => true]);

        $this->addColumn([
            'index' => 'customer_name',
            'label' => trans('wallet::app.admin.wallet.withdrawals.customer') ?? 'العميل',
            'type' => 'string',
            'searchable' => true,
            'closure' => fn ($row) => '<div><span class="font-bold text-gray-900 dark:text-white">'.e($row->customer_name).'</span>'.($row->customer_email ? '<br/><span class="text-xs text-gray-500">'.e($row->customer_email).'</span>' : '').'</div>',
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => trans('wallet::app.admin.wallet.withdrawals.amount') ?? 'المبلغ',
            'type' => 'decimal',
            'sortable' => true,
            'closure' => fn ($row) => '<span class="font-extrabold text-emerald-600 dark:text-emerald-400">'.core()->formatBasePrice($row->amount).'</span>',
        ]);

        $this->addColumn([
            'index' => 'payout_method',
            'label' => 'طريقة السحب',
            'type' => 'string',
            'closure' => function ($row) use ($parseBankDetails) {
                $details = $parseBankDetails($row);
                $method = $details['bank_name'] ?? $details['method'] ?? '—';

                return '<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60">'.e($method).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'account_name',
            'label' => 'اسم صاحب الحساب',
            'type' => 'string',
            'closure' => function ($row) use ($parseBankDetails) {
                $details = $parseBankDetails($row);
                $name = $details['account_name'] ?? '—';

                return '<span class="font-bold text-gray-800 dark:text-gray-200">'.e($name).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'account_number',
            'label' => 'رقم الحساب',
            'type' => 'string',
            'closure' => function ($row) use ($parseBankDetails) {
                $details = $parseBankDetails($row);
                $number = $details['iban'] ?? $details['account_number'] ?? '—';
                if ($number === '—') {
                    return '<span class="text-gray-400">—</span>';
                }

                return '<span class="font-mono font-extrabold text-xs px-2.5 py-1 rounded bg-gray-100 text-gray-900 border border-gray-300 dark:bg-gray-800 dark:text-white dark:border-gray-700 tracking-wider select-all">'.e($number).'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('wallet::app.admin.wallet.withdrawals.status') ?? 'الحالة',
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                $status = $row->raw_status ?? $row->status;

                $notesHtml = '';
                if (! empty($row->rejection_reason)) {
                    $notesHtml = '<div class="mt-1 text-xs font-semibold text-rose-600 dark:text-rose-400" style="color: #dc2626; font-size: 11px; font-weight: 600; margin-top: 3px; max-width: 150px; word-break: break-word;">السبب: '.e($row->rejection_reason).'</div>';
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

        $this->addColumn(['index' => 'admin_name', 'label' => trans('wallet::app.admin.wallet.withdrawals.processed-by') ?? 'نُفِّذ بواسطة', 'type' => 'string']);
        $this->addColumn(['index' => 'created_at', 'label' => trans('wallet::app.admin.wallet.withdrawals.date') ?? 'التاريخ', 'type' => 'datetime', 'sortable' => true]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'index' => 'edit',
            'icon' => 'icon-eye',
            'title' => 'عرض تفاصيل طلب السحب',
            'method' => 'GET',
            'url' => fn ($row) => route('admin.wallet.withdrawals.edit', $row->id),
        ]);

        $this->addAction([
            'index' => 'complete',
            'icon' => 'icon-tick',
            'title' => trans('wallet::app.admin.wallet.withdrawals.complete') ?? 'تأكيد التنفيذ',
            'method' => 'POST',
            'url' => fn ($row) => ($row->raw_status ?? $row->status) === 'pending' ? route('admin.wallet.withdrawals.complete', $row->id) : '',
        ]);

        $this->addAction([
            'index' => 'reject',
            'icon' => 'icon-cross',
            'title' => trans('wallet::app.admin.wallet.withdrawals.reject') ?? 'رفض الطلب',
            'method' => 'POST',
            'url' => fn ($row) => ($row->raw_status ?? $row->status) === 'pending' ? route('admin.wallet.withdrawals.reject', $row->id) : '',
        ]);
    }
}
