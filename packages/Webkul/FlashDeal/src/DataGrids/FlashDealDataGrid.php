<?php

namespace Webkul\FlashDeal\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FlashDealDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('flash_deals')
            ->select(
                'flash_deals.id',
                'flash_deals.title',
                'flash_deals.status',
                'flash_deals.starts_at',
                'flash_deals.ends_at',
                'flash_deals.created_at'
            );

        $this->addFilter('id', 'flash_deals.id');
        $this->addFilter('title', 'flash_deals.title');
        $this->addFilter('status', 'flash_deals.status');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'id',
            'label' => 'المعرف',
            'type' => 'integer',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'title',
            'label' => 'عنوان العرض',
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'الحالة',
            'type' => 'boolean',
            'filterable' => true,
            'sortable' => true,
            'closure' => function ($value) {
                return $value->status ? 'نشط' : 'غير نشط';
            },
        ]);

        $this->addColumn([
            'index' => 'starts_at',
            'label' => 'تاريخ ووقت البدء',
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'ends_at',
            'label' => 'تاريخ ووقت الانتهاء',
            'type' => 'datetime',
            'filterable' => true,
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('marketing.promotions.flash_deals')) {
            $this->addAction([
                'icon' => 'icon-edit',
                'title' => 'تعديل',
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.marketing.promotions.flash_deals.edit', $row->id);
                },
            ]);

            $this->addAction([
                'icon' => 'icon-delete',
                'title' => 'حذف',
                'method' => 'DELETE',
                'url' => function ($row) {
                    return route('admin.marketing.promotions.flash_deals.delete', $row->id);
                },
            ]);
        }
    }
}
