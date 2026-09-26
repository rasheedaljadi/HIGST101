<?php

namespace Webkul\MobileApi\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class MobileApiKeyDataGrid extends DataGrid
{
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('mobile_api_keys')
            ->leftJoin('channels', 'mobile_api_keys.channel_id', '=', 'channels.id')
            ->leftJoin('channel_translations', function ($leftJoin) {
                $leftJoin->on('channel_translations.channel_id', '=', 'channels.id')
                    ->where('channel_translations.locale', core()->getRequestedLocaleCode());
            })
            ->select(
                'mobile_api_keys.id',
                'mobile_api_keys.name',
                'mobile_api_keys.key',
                'mobile_api_keys.status',
                'mobile_api_keys.last_used_at',
                'mobile_api_keys.created_at',
                'channel_translations.name as channel_name'
            );

        $this->addFilter('id', 'mobile_api_keys.id');
        $this->addFilter('name', 'mobile_api_keys.name');
        $this->addFilter('key', 'mobile_api_keys.key');
        $this->addFilter('channel_name', 'channel_translations.name');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('mobile_api::app.admin.datagrid.id'),
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('mobile_api::app.admin.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'key',
            'label' => trans('mobile_api::app.admin.datagrid.key'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'closure' => function ($row) {
                return '<code class="font-mono bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs select-all">'.$row->key.'</code>';
            },
        ]);

        $this->addColumn([
            'index' => 'channel_name',
            'label' => trans('mobile_api::app.admin.datagrid.channel'),
            'type' => 'string',
            'filterable' => true,
            'closure' => function ($row) {
                return $row->channel_name ?: trans('mobile_api::app.admin.datagrid.all-channels');
            },
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('mobile_api::app.admin.datagrid.status'),
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->status) {
                    return '<span class="badge badge-md badge-success">'.trans('mobile_api::app.admin.datagrid.active').'</span>';
                }

                return '<span class="badge badge-md badge-danger">'.trans('mobile_api::app.admin.datagrid.inactive').'</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'last_used_at',
            'label' => trans('mobile_api::app.admin.datagrid.last-used-at'),
            'type' => 'date',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('mobile_api::app.admin.datagrid.created-at'),
            'type' => 'date',
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('mobile_api::app.admin.datagrid.delete'),
            'method' => 'DELETE',
            'url' => function ($row) {
                return route('admin.settings.mobile_api_keys.destroy', $row->id);
            },
        ]);
    }
}
