<?php

namespace Webkul\OfflinePayments\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class OfflinePaymentAccountDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        return DB::table('offline_payment_accounts')
            ->select(
                'offline_payment_accounts.id',
                'offline_payment_accounts.code',
                'offline_payment_accounts.display_name',
                'offline_payment_accounts.provider_name',
                'offline_payment_accounts.recipient_name',
                'offline_payment_accounts.is_active',
                DB::raw('(SELECT GROUP_CONCAT(currencies.code SEPARATOR " • ") FROM offline_payment_destinations JOIN currencies ON offline_payment_destinations.currency_id = currencies.id WHERE offline_payment_destinations.offline_payment_account_id = offline_payment_accounts.id AND offline_payment_destinations.is_active = 1) as currencies_summary')
            )
            ->whereNull('offline_payment_accounts.deleted_at');
    }

    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('offline_payments::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'display_name',
            'label' => trans('offline_payments::app.admin.datagrid.display-name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'provider_name',
            'label' => trans('offline_payments::app.admin.datagrid.provider-name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'recipient_name',
            'label' => trans('offline_payments::app.admin.datagrid.recipient-name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'currencies_summary',
            'label' => trans('offline_payments::app.admin.datagrid.currency'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                return $row->currencies_summary ?: '-';
            },
        ]);

        $this->addColumn([
            'index' => 'is_active',
            'label' => trans('offline_payments::app.admin.datagrid.status'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-md align-text-bottom badge-success">'.trans('offline_payments::app.admin.datagrid.active').'</span>';
                }

                return '<span class="badge badge-md align-text-bottom badge-danger">'.trans('offline_payments::app.admin.datagrid.inactive').'</span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.offline_payment_accounts.edit')) {
            $this->addAction([
                'index' => 'edit',
                'icon' => 'icon-edit',
                'title' => trans('offline_payments::app.admin.datagrid.edit'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.settings.offline_accounts.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.offline_payment_accounts.delete')) {
            $this->addAction([
                'index' => 'delete',
                'icon' => 'icon-delete',
                'title' => trans('offline_payments::app.admin.datagrid.delete'),
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.settings.offline_accounts.delete', $row->id);
                },
            ]);
        }
    }
}
