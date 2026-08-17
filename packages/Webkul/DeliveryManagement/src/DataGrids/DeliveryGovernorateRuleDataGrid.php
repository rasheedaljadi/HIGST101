<?php

namespace Webkul\DeliveryManagement\DataGrids;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class DeliveryGovernorateRuleDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('delivery_governorate_rules')
            ->leftJoin('country_states', function ($join) {
                $join->on('delivery_governorate_rules.state_code', '=', 'country_states.code')
                    ->where('country_states.country_code', '=', 'YE');
            })
            ->leftJoin('country_state_translations', function ($join) {
                $join->on('country_states.id', '=', 'country_state_translations.country_state_id')
                    ->where('country_state_translations.locale', '=', 'ar');
            })
            ->select(
                'delivery_governorate_rules.id',
                'delivery_governorate_rules.state_code',
                'delivery_governorate_rules.delivery_type',
                'delivery_governorate_rules.is_enabled',
                'delivery_governorate_rules.allowed_payment_methods',
                'delivery_governorate_rules.delivery_fee',
                'delivery_governorate_rules.min_order_amount',
                'delivery_governorate_rules.updated_at',
                DB::raw('COALESCE(country_state_translations.default_name, country_states.default_name, delivery_governorate_rules.state_code) as governorate_name')
            );

        $this->addFilter('id', 'delivery_governorate_rules.id');
        $this->addFilter('state_code', 'delivery_governorate_rules.state_code');
        $this->addFilter('delivery_type', 'delivery_governorate_rules.delivery_type');
        $this->addFilter('is_enabled', 'delivery_governorate_rules.is_enabled');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('delivery::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'governorate_name',
            'label' => trans('delivery::app.admin.rules.governorate'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-bold text-gray-800 dark:text-white">'.$row->governorate_name.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'state_code',
            'label' => trans('delivery::app.admin.rules.code'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">'.$row->state_code.'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'delivery_type',
            'label' => 'نوع التسليم',
            'type' => 'string',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                return $row->delivery_type === 'home_delivery'
                    ? '<span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800 font-semibold">🏠 توصيل منزلي</span>'
                    : '<span class="px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-800 font-semibold">📍 نقطة استلام</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'delivery_fee',
            'label' => trans('delivery::app.admin.rules.delivery-fee'),
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                return '<span class="font-semibold text-gray-800 dark:text-white">'.number_format((float) $row->delivery_fee, 2).' ر.ي</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'allowed_payment_methods',
            'label' => 'طرق الدفع المسموحة',
            'type' => 'string',
            'closure' => function ($row) {
                $methods = is_string($row->allowed_payment_methods) ? json_decode($row->allowed_payment_methods, true) : (array) $row->allowed_payment_methods;
                $methods = is_array($methods) ? $methods : [];
                $labels = [];
                foreach ($methods as $m) {
                    if ($m === 'cashondelivery') {
                        $labels[] = '<span class="px-1.5 py-0.5 rounded text-[11px] bg-emerald-100 text-emerald-800">الدفع عند الاستلام</span>';
                    } elseif ($m === 'moneytransfer') {
                        $labels[] = '<span class="px-1.5 py-0.5 rounded text-[11px] bg-amber-100 text-amber-800">حوالة بنكية</span>';
                    } else {
                        $labels[] = '<span class="px-1.5 py-0.5 rounded text-[11px] bg-gray-100 text-gray-800">'.$m.'</span>';
                    }
                }

                return ! empty($labels) ? implode(' ', $labels) : '<span class="text-xs text-rose-500">غير محدد</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'is_enabled',
            'label' => trans('delivery::app.admin.rules.status'),
            'type' => 'boolean',
            'filterable' => true,
            'closure' => function ($row) {
                return $row->is_enabled
                    ? '<span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 font-medium">✓ مفعّل</span>'
                    : '<span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 font-medium">✗ معطّل</span>';
            },
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('delivery::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url' => function ($row) {
                return route('admin.delivery.rules.edit', $row->id);
            },
        ]);
    }
}
